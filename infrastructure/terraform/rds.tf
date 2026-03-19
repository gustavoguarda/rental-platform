# ==============================================================================
# RDS PostgreSQL — Primary Data Store
# ==============================================================================
#
# WHY PostgreSQL over MySQL/Aurora:
# - JSONB: native JSON column type for amenities, pricing rules metadata
# - Better indexing: GIN indexes on JSONB, partial indexes for status queries
# - Strong data integrity: CHECK constraints, EXCLUDE constraints for date ranges
# - Cost: RDS PostgreSQL is cheaper than Aurora for this workload size
#
# Production vs Staging differences:
# ┌──────────────────────┬─────────────┬──────────────┐
# │ Feature              │ Staging     │ Production   │
# ├──────────────────────┼─────────────┼──────────────┤
# │ Multi-AZ             │ No          │ Yes          │
# │ Backup retention     │ 1 day       │ 7 days       │
# │ Performance Insights │ No          │ Yes          │
# │ Deletion protection  │ No          │ Yes          │
# │ Final snapshot       │ Skip        │ Required     │
# └──────────────────────┴─────────────┴──────────────┘
#
# WHY gp3 storage:
# - 3000 IOPS baseline (free) vs gp2's burst model
# - 20% cheaper than gp2 for same performance
# - max_allocated_storage enables auto-scaling up to 100GB without downtime
# ==============================================================================

resource "aws_db_subnet_group" "main" {
  name       = "${var.app_name}-${var.environment}"
  subnet_ids = module.vpc.private_subnets  # DB in private subnets — no public access
}

resource "aws_db_instance" "main" {
  identifier = "${var.app_name}-${var.environment}"

  engine         = "postgres"
  engine_version = "16.3"
  instance_class = var.db_instance_class  # db.t3.medium: 2 vCPU, 4GB RAM

  allocated_storage     = 20    # Start small
  max_allocated_storage = 100   # Auto-scale storage up to 100GB (zero downtime)
  storage_type          = "gp3" # 3000 IOPS baseline, 20% cheaper than gp2
  storage_encrypted     = true  # AES-256 encryption at rest

  db_name  = var.db_name
  username = "platform"
  password = random_password.db_password.result  # Generated, stored in SSM

  # Multi-AZ: synchronous standby replica in another AZ
  # Automatic failover in ~60-120s if primary fails
  multi_az = var.environment == "production"

  db_subnet_group_name   = aws_db_subnet_group.main.name
  vpc_security_group_ids = [aws_security_group.rds.id]  # Only ECS can connect

  # Backups: automated daily snapshots
  backup_retention_period = var.environment == "production" ? 7 : 1
  backup_window           = "03:00-04:00"           # Off-peak hours (UTC)
  maintenance_window      = "Mon:04:00-Mon:05:00"   # After backup window

  # Snapshot behavior on destroy
  skip_final_snapshot       = var.environment != "production"
  final_snapshot_identifier = var.environment == "production" ? "${var.app_name}-final" : null

  # Performance Insights: free for 7 days retention, shows query-level metrics
  performance_insights_enabled = var.environment == "production"

  # Prevent accidental deletion via Terraform or AWS console
  deletion_protection = var.environment == "production"
}

# ==============================================================================
# Secrets Management — SSM Parameter Store
# ==============================================================================
# WHY SSM over Secrets Manager:
# - Free tier: SSM standard parameters are free, Secrets Manager is $0.40/secret/mo
# - ECS native integration: secrets injected at container start, not in env vars
# - Encrypted with AWS KMS (SecureString type)
# ==============================================================================

resource "random_password" "db_password" {
  length  = 32
  special = true
}

resource "aws_ssm_parameter" "db_password" {
  name  = "/${var.app_name}/${var.environment}/db-password"
  type  = "SecureString"  # Encrypted with AWS-managed KMS key
  value = random_password.db_password.result
}

resource "aws_ssm_parameter" "app_key" {
  name  = "/${var.app_name}/${var.environment}/app-key"
  type  = "SecureString"
  value = "base64:placeholder-generate-with-artisan"

  # After initial creation, app key is managed manually via `artisan key:generate`
  lifecycle {
    ignore_changes = [value]
  }
}

resource "aws_ssm_parameter" "openai_key" {
  name  = "/${var.app_name}/${var.environment}/openai-api-key"
  type  = "SecureString"
  value = var.openai_api_key

  # Updated manually when rotating API keys
  lifecycle {
    ignore_changes = [value]
  }
}
