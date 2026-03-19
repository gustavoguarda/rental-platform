# ==============================================================================
# Security Groups — Network-Level Access Control (Least Privilege)
# ==============================================================================
#
# Traffic flow and what each security group allows:
#
#   Internet (0.0.0.0/0)
#     │ ports 80, 443
#     ▼
#   [SG: alb] ── ALB (public subnets)
#     │ port 80 (HTTP)
#     ▼
#   [SG: ecs] ── ECS Tasks (private subnets)
#     │ port 5432          │ port 6379
#     ▼                    ▼
#   [SG: rds] ── RDS    [SG: redis] ── ElastiCache
#
# KEY PRINCIPLE: each layer only accepts traffic from the layer above.
# - RDS cannot be reached from ALB (must go through ECS)
# - Redis cannot be reached from the internet (private subnet + SG)
# - ECS tasks cannot be reached directly from internet (only via ALB)
# ==============================================================================

# ALB: accepts HTTP/HTTPS from the internet
resource "aws_security_group" "alb" {
  name_prefix = "${var.app_name}-alb-"
  vpc_id      = module.vpc.vpc_id

  ingress {
    description = "HTTP from internet (redirects to HTTPS)"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "HTTPS from internet"
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    description = "Allow all outbound (to reach ECS tasks in private subnets)"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

# ECS: accepts traffic ONLY from ALB, not from internet
resource "aws_security_group" "ecs" {
  name_prefix = "${var.app_name}-ecs-"
  vpc_id      = module.vpc.vpc_id

  ingress {
    description     = "HTTP from ALB only"
    from_port       = 80
    to_port         = 80
    protocol        = "tcp"
    security_groups = [aws_security_group.alb.id]  # Source: ALB security group
  }

  egress {
    description = "Allow all outbound (DB, Redis, internet via NAT for OpenAI API)"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

# RDS: accepts connections ONLY from ECS tasks
resource "aws_security_group" "rds" {
  name_prefix = "${var.app_name}-rds-"
  vpc_id      = module.vpc.vpc_id

  ingress {
    description     = "PostgreSQL from ECS only"
    from_port       = 5432
    to_port         = 5432
    protocol        = "tcp"
    security_groups = [aws_security_group.ecs.id]  # Source: ECS security group
  }

  # No egress rules needed — RDS doesn't initiate outbound connections
}

# Redis: accepts connections ONLY from ECS tasks
resource "aws_security_group" "redis" {
  name_prefix = "${var.app_name}-redis-"
  vpc_id      = module.vpc.vpc_id

  ingress {
    description     = "Redis from ECS only"
    from_port       = 6379
    to_port         = 6379
    protocol        = "tcp"
    security_groups = [aws_security_group.ecs.id]  # Source: ECS security group
  }
}

# ==============================================================================
# IAM Roles — Application-Level Permissions (Least Privilege)
# ==============================================================================
#
# Two separate roles for separation of concerns:
#
# 1. Execution Role: used by ECS agent (not the app)
#    - Pull Docker images from ECR
#    - Read secrets from SSM Parameter Store
#    - Write logs to CloudWatch
#
# 2. Task Role: used by the application code
#    - Read/write to S3 (property images, exports)
#    - Any other AWS SDK calls the app makes
#
# WHY separate roles:
# - Execution role has infrastructure permissions (ECR, SSM) — app code shouldn't
# - Task role has business permissions (S3) — ECS agent doesn't need these
# - If app is compromised, attacker gets task role only, not ECR/SSM access
# ==============================================================================

# Execution Role: ECS agent uses this to set up the task
resource "aws_iam_role" "ecs_execution" {
  name = "${var.app_name}-ecs-execution-${var.environment}"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action = "sts:AssumeRole"
      Effect = "Allow"
      Principal = { Service = "ecs-tasks.amazonaws.com" }
    }]
  })
}

# AWS managed policy: ECR image pull + CloudWatch logs
resource "aws_iam_role_policy_attachment" "ecs_execution" {
  role       = aws_iam_role.ecs_execution.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}

# Custom policy: read secrets from SSM (scoped to our app's parameter path only)
resource "aws_iam_role_policy" "ecs_execution_ssm" {
  name = "ssm-access"
  role = aws_iam_role.ecs_execution.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect   = "Allow"
      Action   = ["ssm:GetParameters", "ssm:GetParameter"]
      Resource = "arn:aws:ssm:${var.aws_region}:*:parameter/${var.app_name}/*"
    }]
  })
}

# Task Role: the application code uses this for AWS SDK calls
resource "aws_iam_role" "ecs_task" {
  name = "${var.app_name}-ecs-task-${var.environment}"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action = "sts:AssumeRole"
      Effect = "Allow"
      Principal = { Service = "ecs-tasks.amazonaws.com" }
    }]
  })
}

# S3 access: read/write property images and exports (scoped to our bucket only)
resource "aws_iam_role_policy" "ecs_task_s3" {
  name = "s3-access"
  role = aws_iam_role.ecs_task.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect   = "Allow"
      Action   = ["s3:PutObject", "s3:GetObject", "s3:DeleteObject"]
      Resource = "${aws_s3_bucket.assets.arn}/*"  # Only our assets bucket, not all S3
    }]
  })
}
