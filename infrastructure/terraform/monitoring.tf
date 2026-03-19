# ==============================================================================
# Monitoring & Auto-Scaling
# ==============================================================================
#
# Three layers of observability:
#
# 1. LOGS: CloudWatch Logs ← all container stdout/stderr
#    - nginx access logs: request rate, response codes, latency
#    - php-fpm logs: application errors, slow queries
#    - worker logs: job processing, failures, retries
#
# 2. METRICS + ALARMS: CloudWatch Metrics → SNS → PagerDuty/Slack
#    - API latency > 2s for 3 min → alert (possible DB bottleneck or AI timeout)
#    - 5xx errors > 10 in 5 min → alert (application error spike)
#    - RDS CPU > 80% for 15 min → alert (need query optimization or instance upgrade)
#
# 3. AUTO-SCALING: adjust ECS task count based on load
#    - Scale out at 70% CPU (add tasks in 60s)
#    - Scale in at 70% CPU (remove tasks after 300s cooldown)
#    - Range: 2 (minimum for HA) → 6 (cost cap)
#
# WHY these specific thresholds:
# - 2s API latency: most calls should be < 200ms; 2s means something is wrong
#   (likely a slow DB query or AI call timing out)
# - 10 5xx errors / 5min: occasional 5xx is normal (timeouts, etc.)
#   but 10+ indicates a systemic issue
# - 80% RDS CPU for 15 min: sustained high CPU means we need to scale up,
#   optimize queries, or add read replicas
# - 70% CPU auto-scaling target: leaves headroom for traffic spikes
#   while keeping costs reasonable
# ==============================================================================

resource "aws_cloudwatch_log_group" "ecs" {
  name              = "/ecs/${var.app_name}-${var.environment}"
  retention_in_days = var.environment == "production" ? 30 : 7  # Cost control
}

# --- ALARM: API Response Time ---
# Triggers when average response time exceeds 2 seconds for 3 consecutive minutes.
# Common causes: slow DB queries, AI API timeouts, connection pool exhaustion.
resource "aws_cloudwatch_metric_alarm" "api_latency" {
  alarm_name          = "${var.app_name}-api-high-latency"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 3          # 3 consecutive breaches needed (avoids false alarms)
  metric_name         = "TargetResponseTime"
  namespace           = "AWS/ApplicationELB"
  period              = 60         # Check every 60 seconds
  statistic           = "Average"
  threshold           = 2          # 2 seconds
  alarm_description   = "API response time > 2s for 3 consecutive minutes"

  dimensions = {
    LoadBalancer = aws_lb.main.arn_suffix
    TargetGroup  = aws_lb_target_group.api.arn_suffix
  }

  alarm_actions = [aws_sns_topic.alerts.arn]  # → PagerDuty / Slack integration
}

# --- ALARM: 5xx Error Spike ---
# Triggers when more than 10 server errors occur within 5 minutes.
# Common causes: deployment regression, database connection failure, OOM kill.
resource "aws_cloudwatch_metric_alarm" "api_5xx" {
  alarm_name          = "${var.app_name}-api-5xx-errors"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2          # 2 consecutive periods (10 min total)
  metric_name         = "HTTPCode_Target_5XX_Count"
  namespace           = "AWS/ApplicationELB"
  period              = 300        # 5-minute window
  statistic           = "Sum"
  threshold           = 10
  alarm_description   = "More than 10 5xx errors in 5 minutes"

  dimensions = {
    LoadBalancer = aws_lb.main.arn_suffix
    TargetGroup  = aws_lb_target_group.api.arn_suffix
  }

  alarm_actions = [aws_sns_topic.alerts.arn]
}

# --- ALARM: Database CPU ---
# Triggers when RDS CPU stays above 80% for 15 minutes.
# Common causes: unindexed queries, N+1 problems, connection surge.
# Action: add read replicas, optimize queries, or scale instance up.
resource "aws_cloudwatch_metric_alarm" "rds_cpu" {
  alarm_name          = "${var.app_name}-rds-high-cpu"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 3          # 3 × 5min = 15 minutes sustained
  metric_name         = "CPUUtilization"
  namespace           = "AWS/RDS"
  period              = 300
  statistic           = "Average"
  threshold           = 80
  alarm_description   = "RDS CPU > 80% for 15 minutes"

  dimensions = {
    DBInstanceIdentifier = aws_db_instance.main.identifier
  }

  alarm_actions = [aws_sns_topic.alerts.arn]
}

# ==============================================================================
# ECS Auto-Scaling — CPU-Based Target Tracking
# ==============================================================================
#
# HOW IT WORKS:
# - AWS monitors average CPU across all API tasks
# - If CPU > 70% → add tasks (scale out in 60s)
# - If CPU < 70% → remove tasks (scale in after 300s cooldown)
#
# WHY target tracking over step scaling:
# - Simpler: one number to configure (target CPU %)
# - Self-adjusting: AWS calculates how many tasks needed to reach target
# - Asymmetric cooldowns: fast scale-out (60s) for traffic spikes,
#   slow scale-in (300s) to avoid flapping
#
# Vacation rental traffic patterns:
# - Weekday evenings: booking searches spike after work hours
# - Sunday evenings: planning next trip
# - Seasonal: summer, holidays, spring break → sustained high traffic
# ==============================================================================

resource "aws_appautoscaling_target" "api" {
  max_capacity       = 6                       # Cost cap: max 6 tasks × $0.04/hr ≈ $173/mo
  min_capacity       = var.app_desired_count    # 2 minimum for high availability
  resource_id        = "service/${aws_ecs_cluster.main.name}/${aws_ecs_service.api.name}"
  scalable_dimension = "ecs:service:DesiredCount"
  service_namespace  = "ecs"
}

resource "aws_appautoscaling_policy" "api_cpu" {
  name               = "${var.app_name}-api-cpu-scaling"
  policy_type        = "TargetTrackingScaling"
  resource_id        = aws_appautoscaling_target.api.resource_id
  scalable_dimension = aws_appautoscaling_target.api.scalable_dimension
  service_namespace  = aws_appautoscaling_target.api.service_namespace

  target_tracking_scaling_policy_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ECSServiceAverageCPUUtilization"
    }
    target_value       = 70   # Keep average CPU around 70%
    scale_in_cooldown  = 300  # Wait 5 min before removing tasks (avoid flapping)
    scale_out_cooldown = 60   # Add tasks quickly when load increases
  }
}

# SNS topic for alarm notifications → subscribe PagerDuty, Slack, email
resource "aws_sns_topic" "alerts" {
  name = "${var.app_name}-alerts-${var.environment}"
}
