# ==============================================================================
# ECS Fargate — Serverless Container Orchestration
# ==============================================================================
#
# WHY Fargate over EC2:
# - No server management — AWS handles patching, scaling the underlying hosts
# - Pay-per-task — no idle EC2 instances. Good for variable traffic (vacation rentals
#   have seasonal spikes: summer, holidays, spring break)
# - Pairs well with auto-scaling: scale from 2 → 6 tasks in ~60s
#
# Architecture:
#   ALB → [Task: nginx + php-fpm] × N  (API serving)
#         [Task: queue worker] × 1      (Async jobs: channel sync, AI generation)
#
# WHY 2 containers in API task (sidecar pattern):
# - nginx handles static files, connection buffering, request queuing
# - php-fpm handles PHP execution
# - They communicate via localhost (same task = same network namespace)
# - Same pattern as the local docker-compose setup → dev/prod parity
# ==============================================================================

resource "aws_ecs_cluster" "main" {
  name = "${var.app_name}-${var.environment}"

  # Container Insights: built-in CloudWatch metrics for CPU, memory, network per task
  # Costs ~$0.01/task/hour but gives visibility into resource utilization
  setting {
    name  = "containerInsights"
    value = "enabled"
  }

  # ECS Exec: allows `aws ecs execute-command` for debugging (like docker exec)
  configuration {
    execute_command_configuration {
      logging = "OVERRIDE"
      log_configuration {
        cloud_watch_log_group_name = aws_cloudwatch_log_group.ecs.name
      }
    }
  }
}

# ------------------------------------------------------------------------------
# API Task Definition — nginx (reverse proxy) + php-fpm (application)
# ------------------------------------------------------------------------------
resource "aws_ecs_task_definition" "api" {
  family                   = "${var.app_name}-api"
  network_mode             = "awsvpc"           # Required for Fargate; each task gets its own ENI
  requires_compatibilities = ["FARGATE"]
  cpu                      = var.ecs_cpu         # 512 (0.5 vCPU) — enough for PHP API
  memory                   = var.ecs_memory      # 1024 MB — shared between nginx + php-fpm
  execution_role_arn       = aws_iam_role.ecs_execution.arn  # Pulls images, reads SSM secrets
  task_role_arn            = aws_iam_role.ecs_task.arn        # App-level permissions (S3, etc.)

  container_definitions = jsonencode([
    {
      name  = "nginx"
      image = "${aws_ecr_repository.app.repository_url}:nginx-latest"
      portMappings = [{
        containerPort = 80
        protocol      = "tcp"
      }]
      # Wait for php-fpm to start before accepting requests
      dependsOn = [{
        containerName = "php-fpm"
        condition     = "START"
      }]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.ecs.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "nginx"
        }
      }
    },
    {
      name  = "php-fpm"
      image = "${aws_ecr_repository.app.repository_url}:api-latest"

      # Non-sensitive config as environment variables
      environment = [
        { name = "APP_ENV", value = var.environment },
        { name = "DB_CONNECTION", value = "pgsql" },
        { name = "DB_HOST", value = aws_db_instance.main.address },
        { name = "DB_PORT", value = "5432" },
        { name = "DB_DATABASE", value = var.db_name },
        { name = "REDIS_HOST", value = aws_elasticache_cluster.redis.cache_nodes[0].address },
        { name = "CACHE_DRIVER", value = "redis" },
        { name = "QUEUE_CONNECTION", value = "redis" },
      ]

      # Sensitive values from SSM Parameter Store (never in env vars or code)
      # ECS injects these at container start time
      secrets = [
        { name = "DB_PASSWORD", valueFrom = aws_ssm_parameter.db_password.arn },
        { name = "APP_KEY", valueFrom = aws_ssm_parameter.app_key.arn },
        { name = "OPENAI_API_KEY", valueFrom = aws_ssm_parameter.openai_key.arn },
      ]

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.ecs.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "php-fpm"
        }
      }
    }
  ])
}

# ------------------------------------------------------------------------------
# API Service — manages desired count, rolling deploys, ALB integration
# ------------------------------------------------------------------------------
resource "aws_ecs_service" "api" {
  name            = "${var.app_name}-api"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.api.arn
  desired_count   = var.app_desired_count  # 2 minimum for high availability
  launch_type     = "FARGATE"

  network_configuration {
    subnets          = module.vpc.private_subnets     # Private — no public IP
    security_groups  = [aws_security_group.ecs.id]    # Only accepts traffic from ALB
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.api.arn
    container_name   = "nginx"    # ALB routes to nginx, which proxies to php-fpm
    container_port   = 80
  }

  # Circuit breaker: if new deployment keeps failing health checks,
  # automatically roll back to the previous working version
  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }

  depends_on = [aws_lb_listener.https]
}

# ------------------------------------------------------------------------------
# Queue Worker — processes async jobs (channel sync, AI description generation)
# ------------------------------------------------------------------------------
# WHY separate from API:
# - Different resource profile: workers are CPU-bound (AI calls), API is I/O-bound
# - Independent scaling: can add more workers during peak booking seasons
# - Fault isolation: a stuck queue job doesn't affect API response times
# ------------------------------------------------------------------------------
resource "aws_ecs_task_definition" "worker" {
  family                   = "${var.app_name}-worker"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]
  cpu                      = 256    # 0.25 vCPU — workers process one job at a time
  memory                   = 512    # 512 MB — enough for PHP + single job
  execution_role_arn       = aws_iam_role.ecs_execution.arn
  task_role_arn            = aws_iam_role.ecs_task.arn

  container_definitions = jsonencode([{
    name  = "worker"
    image = "${aws_ecr_repository.app.repository_url}:api-latest"  # Same image as API

    # Laravel queue worker command:
    # --sleep=3    → poll Redis every 3s when queue is empty (reduces Redis load)
    # --tries=3    → retry failed jobs up to 3 times
    # --max-time=3600 → restart worker every hour (prevents memory leaks)
    command = ["php", "artisan", "queue:work", "--sleep=3", "--tries=3", "--max-time=3600"]

    environment = [
      { name = "APP_ENV", value = var.environment },
      { name = "DB_HOST", value = aws_db_instance.main.address },
      { name = "DB_DATABASE", value = var.db_name },
      { name = "REDIS_HOST", value = aws_elasticache_cluster.redis.cache_nodes[0].address },
      { name = "QUEUE_CONNECTION", value = "redis" },
    ]
    secrets = [
      { name = "DB_PASSWORD", valueFrom = aws_ssm_parameter.db_password.arn },
      { name = "APP_KEY", valueFrom = aws_ssm_parameter.app_key.arn },
      { name = "OPENAI_API_KEY", valueFrom = aws_ssm_parameter.openai_key.arn },
    ]
    logConfiguration = {
      logDriver = "awslogs"
      options = {
        "awslogs-group"         = aws_cloudwatch_log_group.ecs.name
        "awslogs-region"        = var.aws_region
        "awslogs-stream-prefix" = "worker"
      }
    }
  }])
}

resource "aws_ecs_service" "worker" {
  name            = "${var.app_name}-worker"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.worker.arn
  desired_count   = 1               # Single worker; scale up during peak seasons
  launch_type     = "FARGATE"

  network_configuration {
    subnets          = module.vpc.private_subnets
    security_groups  = [aws_security_group.ecs.id]
    assign_public_ip = false
  }
}

# ------------------------------------------------------------------------------
# ECR — Docker image registry
# ------------------------------------------------------------------------------
resource "aws_ecr_repository" "app" {
  name                 = var.app_name
  image_tag_mutability = "MUTABLE"   # Allows :latest tag (CI/CD overwrites on deploy)

  # Scan images for CVEs on every push
  image_scanning_configuration {
    scan_on_push = true
  }

  encryption_configuration {
    encryption_type = "AES256"
  }
}

# Keep only last 10 images to control storage costs
resource "aws_ecr_lifecycle_policy" "app" {
  repository = aws_ecr_repository.app.name
  policy = jsonencode({
    rules = [{
      rulePriority = 1
      description  = "Keep last 10 images"
      selection = {
        tagStatus   = "any"
        countType   = "imageCountMoreThan"
        countNumber = 10
      }
      action = { type = "expire" }
    }]
  })
}
