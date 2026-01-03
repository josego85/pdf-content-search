# Production Deployment Guide

Complete guide for deploying PDF Content Search to production.

## Pre-Deployment Checklist

- [ ] Docker & Docker Compose installed on server
- [ ] 8GB+ RAM available
- [ ] 20GB+ disk space (includes Docker images + Ollama models)
- [ ] Ports 8080 available (or custom via `APACHE_PORT`)
- [ ] SSL certificate ready (if using reverse proxy)

## Quick Production Deploy

```bash
# 1. Clone to server
git clone https://github.com/josego85/pdf-content-search.git
cd pdf-content-search

# 2. Generate production secrets
./bin/init-prod-secrets.sh

# 3. Review generated secrets (optional)
cat .env.prod.local

# 4. Start production
make prod
```

**Access:** http://your-server:8080

## Production Architecture

### What's Different from Dev

| Feature | Development | Production |
|---------|-------------|------------|
| **Port** | 80 | 8080 |
| **Debug** | ON | OFF |
| **PHP Memory** | 256M | 512M |
| **Messenger Workers** | 3 workers, 3600s limit | 3 workers, 3600s limit, 512M |
| **Apache Compression** | Brotli quality 6 | Brotli quality 11 + preload hints |
| **Elasticsearch Auth** | Disabled | Enabled |
| **Docker Build** | Single-stage | Multi-stage (smaller) |
| **Opcache** | Disabled | Enabled |
| **Log Level** | `warn` | `error` |
| **Cache Headers** | 1 week | 1 year (immutable) |

### Services

```
Apache (httpd:2.4-alpine)
  ├─ Brotli compression (quality 11)
  ├─ HTTP/2 enabled
  ├─ Security headers
  └─ Proxies to → PHP-FPM

PHP-FPM (php:8.4-fpm-alpine)
  ├─ Opcache enabled
  ├─ 512M memory limit
  └─ Supervisor manages:
      └─ 3x Messenger workers (3600s, 512M each)

PostgreSQL 16
  ├─ Production database (pdf_search_prod)
  └─ Secure password (auto-generated)

Elasticsearch 9.2
  ├─ Authentication enabled
  ├─ 512MB JVM heap
  └─ Vector search (768 dimensions)

Ollama
  ├─ qwen2.5:7b (translation)
  └─ nomic-embed-text (embeddings)
```

## Environment Configuration

### Secret Generation

**Automatic (recommended):**
```bash
./bin/init-prod-secrets.sh
```

Creates `.env.prod.local` with:
```env
APP_SECRET=<32-char-hex>
POSTGRES_PASSWORD=<32-char-hex>
ELASTIC_PASSWORD=<32-char-hex>
```

**Manual:**
```bash
# Generate individual secrets
./bin/generate-secrets.sh 32

# Add to .env.prod.local
echo "APP_SECRET=$(./bin/generate-secrets.sh 32)" >> .env.prod.local
echo "POSTGRES_PASSWORD=$(./bin/generate-secrets.sh 32)" >> .env.prod.local
echo "ELASTIC_PASSWORD=$(./bin/generate-secrets.sh 32)" >> .env.prod.local
```

### Custom Configuration

Edit `.env.prod.local` (NOT committed):

```env
# Custom port
APACHE_PORT=9090

# Custom domain
DEFAULT_URI=https://pdf-search.example.com

# Increase worker count (edit supervisord.conf instead)
```

## SSL/TLS Setup

The application runs on HTTP. Use a reverse proxy (Nginx/Traefik/Caddy) for SSL.

### Nginx Reverse Proxy Example

```nginx
server {
    listen 443 ssl http2;
    server_name pdf-search.example.com;

    ssl_certificate /etc/ssl/certs/pdf-search.crt;
    ssl_certificate_key /etc/ssl/private/pdf-search.key;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Support large PDFs
        client_max_body_size 100M;
    }
}
```

### Traefik Example

```yaml
# docker-compose.override.yml (on server)
services:
  apache:
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.pdf-search.rule=Host(`pdf-search.example.com`)"
      - "traefik.http.routers.pdf-search.entrypoints=websecure"
      - "traefik.http.routers.pdf-search.tls.certresolver=letsencrypt"
```

## Database Management

### Backups

**Automated backup script:**

```bash
#!/bin/bash
# backup-db.sh
BACKUP_DIR="/backups/pdf-search"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

docker compose -p pdf-content-search-prod exec -T database \
  pg_dump -U app_user pdf_search_prod \
  | gzip > "${BACKUP_DIR}/backup_${TIMESTAMP}.sql.gz"

# Keep only last 7 days
find "${BACKUP_DIR}" -name "backup_*.sql.gz" -mtime +7 -delete
```

**Cron job:**
```cron
0 2 * * * /path/to/backup-db.sh
```

### Restore

```bash
# Decompress and restore
gunzip -c backup_20260103_020000.sql.gz | \
  docker compose -p pdf-content-search-prod exec -T database \
  psql -U app_user -d pdf_search_prod
```

## Elasticsearch Management

### Index Management

**Create index manually:**
```bash
docker compose -p pdf-content-search-prod exec php \
  php bin/console app:create-pdf-index
```

**Re-index all PDFs:**
```bash
docker compose -p pdf-content-search-prod exec php \
  php bin/console app:index-pdfs
```

### Snapshots (Recommended)

Configure Elasticsearch snapshots for disaster recovery:

```json
PUT /_snapshot/backup
{
  "type": "fs",
  "settings": {
    "location": "/usr/share/elasticsearch/backups"
  }
}
```

**Create snapshot:**
```bash
curl -X PUT "http://elastic:${ELASTIC_PASSWORD}@localhost:9200/_snapshot/backup/snapshot_1?wait_for_completion=true"
```

## Monitoring

### Health Checks

```bash
# Service status
make status-prod

# Check individual services
docker compose -p pdf-content-search-prod ps

# Health endpoints
curl http://localhost:8080/health  # Application
curl http://localhost:9200/_cluster/health  # Elasticsearch
```

### Logs

```bash
# All logs
make logs-prod

# Specific service
make logs-prod SERVICE=php
make logs-prod SERVICE=elasticsearch

# Messenger worker logs
docker compose -p pdf-content-search-prod exec php \
  tail -f /var/log/supervisor/messenger-worker-*.log
```

### Messenger Queue Monitoring

```bash
# View queue stats
docker compose -p pdf-content-search-prod exec php \
  php bin/console messenger:stats

# Monitor jobs
docker compose -p pdf-content-search-prod exec php \
  php bin/console app:translation:monitor --watch
```

## Performance Optimization

### Already Applied

- ✅ **Brotli Compression** (quality 11) - 30-50% bandwidth reduction
- ✅ **HTTP/2** - Multiplexing + server push
- ✅ **Webpack Code Splitting** - 94% bundle size reduction (661KB → 35KB initial)
- ✅ **Aggressive Caching** - 1 year immutable for versioned assets
- ✅ **Opcache** - Precompiled PHP bytecode
- ✅ **3 Messenger Workers** - Parallel async processing

### Additional Optimizations

#### 1. Increase Messenger Workers

Edit `.docker/prod/supervisor/supervisord.conf`:
```ini
[program:messenger-worker]
numprocs=5  # Increase from 3 to 5
```

Rebuild:
```bash
make rebuild-prod
```

#### 2. Add Redis for Messenger Queue

**Install Redis:**
```yaml
# docker-compose.override.yml (on server)
services:
  redis:
    image: redis:7-alpine
    restart: always
```

**Configure:**
```env
# .env.prod.local
MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages
```

Restart:
```bash
make restart-prod
```

#### 3. Increase Elasticsearch Heap

For large datasets (10K+ PDFs):

Edit `docker-compose.prod.yml`:
```yaml
elasticsearch:
  environment:
    - "ES_JAVA_OPTS=-Xms1g -Xmx1g"  # 1GB heap
```

#### 4. Enable CDN

Serve static assets from CDN (Cloudflare, AWS CloudFront):

```
/build/*.js
/build/*.css
/pdfs/*.pdf
```

Update `DEFAULT_URI` in `.env.prod.local`.

## Security Hardening

### Network Isolation

**Production-only network:**

Edit `docker-compose.prod.yml`:
```yaml
networks:
  default:
    driver: bridge
    internal: false
```

**Firewall rules:**
```bash
# Allow only port 8080 (or custom APACHE_PORT)
ufw allow 8080/tcp
ufw enable
```

### Disable Unused Services

If not using analytics:
```yaml
# docker-compose.override.yml
services:
  elasticsearch:
    profiles: [disabled]  # Only starts if explicitly requested
```

### Rate Limiting

Add Nginx rate limiting:
```nginx
limit_req_zone $binary_remote_addr zone=search:10m rate=10r/s;

location /search {
    limit_req zone=search burst=20 nodelay;
    proxy_pass http://localhost:8080;
}
```

### Security Headers

Already enabled in Apache config:
- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`

Add via reverse proxy:
- `Strict-Transport-Security` (HSTS)
- `Content-Security-Policy`

## Updates & Maintenance

### Update Application

```bash
# Pull latest code
git pull origin main

# Rebuild production images
make rebuild-prod

# Run migrations (if any)
docker compose -p pdf-content-search-prod exec php \
  php bin/console doctrine:migrations:migrate --no-interaction

# Restart
make restart-prod
```

### Update Dependencies

```bash
# Update Composer
docker compose -p pdf-content-search-prod exec php \
  composer update --no-dev --optimize-autoloader

# Update NPM
docker compose -p pdf-content-search-prod exec php \
  npm update

# Rebuild assets
docker compose -p pdf-content-search-prod exec php \
  npm run build

# Restart
make restart-prod
```

### Update Docker Images

```bash
# Pull latest base images
docker compose -p pdf-content-search-prod pull

# Rebuild
make rebuild-prod
```

## Disaster Recovery

### Full Backup

```bash
#!/bin/bash
# full-backup.sh
BACKUP_DIR="/backups/pdf-search"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# 1. Database
docker compose -p pdf-content-search-prod exec -T database \
  pg_dump -U app_user pdf_search_prod \
  | gzip > "${BACKUP_DIR}/db_${TIMESTAMP}.sql.gz"

# 2. PDFs
tar -czf "${BACKUP_DIR}/pdfs_${TIMESTAMP}.tar.gz" public/pdfs/

# 3. Elasticsearch snapshot
curl -X PUT "http://elastic:${ELASTIC_PASSWORD}@localhost:9200/_snapshot/backup/snapshot_${TIMESTAMP}?wait_for_completion=true"

# 4. Environment config
cp .env.prod.local "${BACKUP_DIR}/env.prod.local.${TIMESTAMP}"
```

### Full Restore

```bash
# 1. Restore database
gunzip -c db_backup.sql.gz | \
  docker compose -p pdf-content-search-prod exec -T database \
  psql -U app_user -d pdf_search_prod

# 2. Restore PDFs
tar -xzf pdfs_backup.tar.gz -C public/

# 3. Restore Elasticsearch
curl -X POST "http://elastic:${ELASTIC_PASSWORD}@localhost:9200/_snapshot/backup/snapshot_name/_restore"

# 4. Restore config
cp env.prod.local.backup .env.prod.local

# 5. Restart
make restart-prod
```

## Troubleshooting Production

### High Memory Usage

```bash
# Check memory per service
docker stats

# Increase PHP memory
# Edit .docker/prod/php/php.ini
memory_limit = 1024M

# Rebuild
make rebuild-prod
```

### Slow Translations

```bash
# Check Ollama CPU usage
docker stats pdf-content-search-prod-ollama-1

# Consider smaller model
# Edit .env.prod.local
OLLAMA_MODEL=llama3.2:1b  # Faster, less accurate
```

### Failed Messenger Jobs

```bash
# Check failed messages
docker compose -p pdf-content-search-prod exec php \
  php bin/console messenger:failed:show

# Retry failed
docker compose -p pdf-content-search-prod exec php \
  php bin/console messenger:failed:retry
```

### More Issues

See [troubleshooting.md](troubleshooting.md) for complete guide.

## Next Steps

- [Configuration Guide](configuration.md) - Environment variables
- [Security Guide](reference/security.md) - Security best practices
- [Docker Architecture](reference/docker-architecture.md) - Internal details
