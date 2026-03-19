# ==============================================================================
# ElastiCache Redis — Cache + Message Queue
# ==============================================================================
#
# Redis serves TWO distinct purposes in this platform:
#
# 1. CACHE (database 0) — Cache-aside pattern for availability checks
#    ┌─────────┐    cache hit     ┌─────────┐
#    │  API    │ ──────────────→  │  Redis  │  → return cached result (< 1ms)
#    │ Request │    cache miss    │ db: 0   │
#    │         │ ──→ PostgreSQL ──→ store ──→│  → return fresh result + cache 5min
#    └─────────┘                  └─────────┘
#
#    WHY cache availability:
#    - Availability checks are the highest-traffic endpoint (booking widgets,
#      channel managers polling every few minutes)
#    - Same property+dates query returns same result until a booking changes
#    - 5-minute TTL balances freshness vs database load
#    - Cache invalidation via domain events (BookingCreated → flush property cache)
#
# 2. QUEUE (database 1) — Laravel queue backend for async jobs
#    ┌───────────────┐     ┌─────────┐     ┌──────────┐
#    │ API: booking  │ ──→ │  Redis  │ ──→ │  Worker  │ → SyncChannelAvailability
#    │   created     │     │ db: 1   │     │  (ECS)   │ → GenerateAIDescription
#    └───────────────┘     └─────────┘     └──────────┘
#
#    WHY Redis over SQS for queues:
#    - Already running for cache — no additional service to manage
#    - Lower latency: Redis LPUSH/BRPOP vs SQS polling (200ms minimum)
#    - Laravel native: zero config with `QUEUE_CONNECTION=redis`
#    - For this workload size (< 1000 jobs/hour), Redis is more cost-effective
#    - Trade-off: SQS would be better for durability at massive scale
#
# Production considerations:
# - snapshot_retention: 3 days in prod (disaster recovery)
# - For higher availability: upgrade to Redis Replication Group (primary + replica)
# - For larger workloads: upgrade to cache.r6g.large (13GB RAM)
# ==============================================================================

resource "aws_elasticache_subnet_group" "redis" {
  name       = "${var.app_name}-redis-${var.environment}"
  subnet_ids = module.vpc.private_subnets  # Private — only accessible from ECS
}

resource "aws_elasticache_cluster" "redis" {
  cluster_id           = "${var.app_name}-${var.environment}"
  engine               = "redis"
  engine_version       = "7.1"
  node_type            = "cache.t3.micro"     # 0.5GB RAM — sufficient for cache + queue
  num_cache_nodes      = 1                     # Single node; upgrade to replication group for HA
  parameter_group_name = "default.redis7"
  port                 = 6379

  subnet_group_name  = aws_elasticache_subnet_group.redis.name
  security_group_ids = [aws_security_group.redis.id]  # Only ECS can connect (port 6379)

  # Daily automatic backups (RDB snapshots)
  snapshot_retention_limit = var.environment == "production" ? 3 : 0
}
