# ==============================================================================
# VPC — Network Foundation
# ==============================================================================
#
# Architecture: 2 Availability Zones with public + private subnets each.
#
#   Internet
#     │
#   IGW (Internet Gateway)
#     │
#   ├── Public Subnet AZ-a (10.0.1.0/24)  ← ALB lives here
#   ├── Public Subnet AZ-b (10.0.2.0/24)  ← ALB lives here (multi-AZ)
#     │
#   NAT Gateway (in public subnet, routes outbound traffic from private)
#     │
#   ├── Private Subnet AZ-a (10.0.10.0/24) ← ECS tasks, RDS, Redis
#   ├── Private Subnet AZ-b (10.0.11.0/24) ← ECS tasks, RDS standby
#
# WHY this design:
# - Private subnets: compute and data have NO public IPs, unreachable from internet
# - NAT Gateway: allows private resources to reach internet (pull Docker images,
#   call OpenAI API, etc.) without being publicly accessible
# - 2 AZs: minimum for high availability. RDS Multi-AZ and ECS spread tasks across both
# - Cost optimization: staging uses single NAT (~$32/mo saved), prod uses one per AZ
# ==============================================================================

module "vpc" {
  source  = "terraform-aws-modules/vpc/aws"
  version = "~> 5.0"

  name = "${var.app_name}-${var.environment}"
  cidr = "10.0.0.0/16" # 65,536 IPs — room for future growth

  azs             = ["${var.aws_region}a", "${var.aws_region}b"]
  public_subnets  = ["10.0.1.0/24", "10.0.2.0/24"]   # 254 IPs each — ALB, NAT
  private_subnets = ["10.0.10.0/24", "10.0.11.0/24"]  # 254 IPs each — ECS, RDS, Redis

  # NAT Gateway: allows private subnet → internet (but not internet → private)
  enable_nat_gateway = true
  single_nat_gateway = var.environment == "staging" # Cost: ~$32/mo per NAT

  # Required for ECS Fargate service discovery and RDS DNS resolution
  enable_dns_hostnames = true
  enable_dns_support   = true

  public_subnet_tags = {
    "Type" = "public"
  }

  private_subnet_tags = {
    "Type" = "private"
  }
}
