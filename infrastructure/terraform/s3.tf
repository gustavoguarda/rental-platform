# ==============================================================================
# S3 — Object Storage (Assets + Logs)
# ==============================================================================

# --- Assets Bucket: property images, exports, uploads ---
resource "aws_s3_bucket" "assets" {
  bucket = "${var.app_name}-assets-${var.environment}"
}

# Versioning: recover accidentally deleted/overwritten property images
resource "aws_s3_bucket_versioning" "assets" {
  bucket = aws_s3_bucket.assets.id
  versioning_configuration {
    status = "Enabled"
  }
}

# Server-side encryption at rest (AES-256, free)
resource "aws_s3_bucket_server_side_encryption_configuration" "assets" {
  bucket = aws_s3_bucket.assets.id
  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

# Block ALL public access — assets served via CloudFront (signed URLs)
resource "aws_s3_bucket_public_access_block" "assets" {
  bucket = aws_s3_bucket.assets.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# --- Logs Bucket: ALB access logs, application logs ---
resource "aws_s3_bucket" "logs" {
  bucket = "${var.app_name}-logs-${var.environment}"
}

# Lifecycle: STANDARD → STANDARD_IA (30 days) → Delete (90 days)
# Saves ~40% on storage costs for older logs
resource "aws_s3_bucket_lifecycle_configuration" "logs" {
  bucket = aws_s3_bucket.logs.id

  rule {
    id     = "expire-old-logs"
    status = "Enabled"

    filter {}  # Apply to all objects in bucket

    transition {
      days          = 30
      storage_class = "STANDARD_IA"   # ~40% cheaper, same durability
    }

    expiration {
      days = 90                        # Delete after 90 days
    }
  }
}

# ==============================================================================
# CloudFront — CDN for Angular SPA
# ==============================================================================
#
# WHY CloudFront for the frontend:
# - Global edge caching: < 50ms load time from anywhere in the US
# - S3 origin with OAC: bucket stays private, CloudFront has access
# - SPA routing: 404 → index.html (Angular handles client-side routing)
# - HTTPS included (free with default certificate)
# - Compression: brotli/gzip for JS/CSS bundles (~70% smaller)
#
# Cache strategy:
# - index.html: short TTL (browser always gets latest version)
# - JS/CSS bundles: long TTL (filenames contain content hash)
# ==============================================================================

resource "aws_cloudfront_distribution" "frontend" {
  enabled             = true
  is_ipv6_enabled     = true
  default_root_object = "index.html"
  price_class         = "PriceClass_100"   # US, Canada, Europe only (cheapest tier)

  origin {
    domain_name              = aws_s3_bucket.assets.bucket_regional_domain_name
    origin_id                = "s3-frontend"
    origin_access_control_id = aws_cloudfront_origin_access_control.s3.id
  }

  default_cache_behavior {
    allowed_methods        = ["GET", "HEAD", "OPTIONS"]
    cached_methods         = ["GET", "HEAD"]
    target_origin_id       = "s3-frontend"
    viewer_protocol_policy = "redirect-to-https"
    compress               = true          # Brotli/gzip compression

    forwarded_values {
      query_string = false                  # Static files don't use query strings
      cookies { forward = "none" }
    }

    min_ttl     = 0
    default_ttl = 3600     # 1 hour
    max_ttl     = 86400    # 24 hours
  }

  # SPA routing: Angular uses client-side routing (/properties, /bookings)
  # When user refreshes on /properties, S3 returns 404 (no such file)
  # CloudFront catches the 404 and serves index.html instead → Angular router takes over
  custom_error_response {
    error_code         = 404
    response_code      = 200
    response_page_path = "/index.html"
  }

  restrictions {
    geo_restriction { restriction_type = "none" }
  }

  viewer_certificate {
    cloudfront_default_certificate = true   # *.cloudfront.net cert (free)
  }
}

# Origin Access Control: CloudFront authenticates to S3 using SigV4
# S3 bucket stays private — only CloudFront can read from it
resource "aws_cloudfront_origin_access_control" "s3" {
  name                              = "${var.app_name}-s3-oac"
  origin_access_control_origin_type = "s3"
  signing_behavior                  = "always"
  signing_protocol                  = "sigv4"
}
