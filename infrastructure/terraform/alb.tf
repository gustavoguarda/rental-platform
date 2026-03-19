# ==============================================================================
# Application Load Balancer — HTTPS Termination + Request Routing
# ==============================================================================
#
# Traffic flow:
#   Internet → ALB (public subnets) → ECS tasks (private subnets)
#
# WHY ALB over NLB:
# - HTTP/HTTPS awareness: can route by path, host header, HTTP method
# - Built-in health checks with HTTP status code matching
# - Access logs for debugging and audit
# - WAF integration if needed later
#
# Security:
# - TLS 1.3 only (ELBSecurityPolicy-TLS13-1-2-2021-06)
# - HTTP → HTTPS redirect (301) — no plaintext traffic
# - ACM certificate with automatic renewal
# ==============================================================================

resource "aws_lb" "main" {
  name               = "${var.app_name}-${var.environment}"
  internal           = false                          # Internet-facing
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb.id]    # Accepts 80/443 from internet
  subnets            = module.vpc.public_subnets       # Must be in public subnets

  enable_deletion_protection = var.environment == "production"

  # Access logs → S3 for debugging, compliance, and traffic analysis
  access_logs {
    bucket  = aws_s3_bucket.logs.id
    prefix  = "alb"
    enabled = true
  }
}

# Target group: routes to ECS task IPs (awsvpc network mode)
resource "aws_lb_target_group" "api" {
  name        = "${var.app_name}-api-${var.environment}"
  port        = 80
  protocol    = "HTTP"              # ALB → ECS is HTTP (TLS terminated at ALB)
  vpc_id      = module.vpc.vpc_id
  target_type = "ip"                # Required for Fargate (no EC2 instance IDs)

  # Health check: ALB pings /api/v1/health every 30s
  # Task is considered healthy after 2 consecutive 200s
  # Task is considered unhealthy after 3 consecutive failures
  health_check {
    path                = "/api/v1/health"   # Matches our health endpoint in routes/api.php
    healthy_threshold   = 2
    unhealthy_threshold = 3
    timeout             = 5
    interval            = 30
    matcher             = "200"
  }

  # How long to wait for in-flight requests before deregistering a task
  # 30s is enough for our API (most requests < 2s, AI requests < 15s)
  deregistration_delay = 30
}

# HTTPS listener (port 443) — primary traffic
resource "aws_lb_listener" "https" {
  load_balancer_arn = aws_lb.main.arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS13-1-2-2021-06"  # TLS 1.3 + 1.2 only
  certificate_arn   = aws_acm_certificate.main.arn

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.api.arn
  }
}

# HTTP listener (port 80) — redirect to HTTPS
resource "aws_lb_listener" "http_redirect" {
  load_balancer_arn = aws_lb.main.arn
  port              = "80"
  protocol          = "HTTP"

  default_action {
    type = "redirect"
    redirect {
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"    # Permanent redirect — browsers cache this
    }
  }
}

# ACM Certificate — free, auto-renewed by AWS
resource "aws_acm_certificate" "main" {
  domain_name       = var.domain_name
  validation_method = "DNS"         # Add CNAME to DNS → auto-validated

  lifecycle {
    create_before_destroy = true    # No downtime during certificate rotation
  }
}
