# Configuration Guide

Complete reference for environment variables and advanced configuration.

> **Quick Start?** See [getting-started.md](getting-started.md) first.

## Environment Variables

### Application

| Variable | Dev Default | Prod Default | Description |
|----------|-------------|--------------|-------------|
| `APP_ENV` | `dev` | `prod` | Environment mode |
| `APP_DEBUG` | `1` | `0` | Debug mode (shows errors) |
| `APP_SECRET` | `change-me` | Auto-generated | Symfony secret key (32 chars) |
| `DEFAULT_URI` | `http://localhost` | Set in `.env.prod.local` | Base URL |

### Database

| Variable | Dev Default | Prod Default | Description |
|----------|-------------|--------------|-------------|
| `POSTGRES_VERSION` | `16` | `16` | PostgreSQL version |
| `POSTGRES_DB` | `pdf_search` | `pdf_search_prod` | Database name |
| `POSTGRES_USER` | `app_user` | `app_user` | Database user |
| `POSTGRES_PASSWORD` | `dev_password` | Auto-generated | Database password |
| `POSTGRES_HOST` | `database` | `database` | Hostname (Docker service) |
| `POSTGRES_PORT` | `5432` | `5432` | Port |
| `DATABASE_URL` | Auto-built | Auto-built | Full connection string |

**Connection string format:**
```
postgresql://${POSTGRES_USER}:${POSTGRES_PASSWORD}@${POSTGRES_HOST}:${POSTGRES_PORT}/${POSTGRES_DB}?serverVersion=${POSTGRES_VERSION}&charset=utf8
```

### Elasticsearch

| Variable | Dev Default | Prod Default | Description |
|----------|-------------|--------------|-------------|
| `ELASTICSEARCH_HOST` | `http://elasticsearch:9200` | `http://elastic:${ELASTIC_PASSWORD}@elasticsearch:9200` | Elasticsearch URL |
| `ELASTICSEARCH_INDEX_PDFS` | `pdf_pages` | `pdf_pages` | Index name |
| `ELASTIC_PASSWORD` | `ignored_in_dev` | Auto-generated | ES password (prod only) |

**Index settings:**
- Shards: 1
- Replicas: 0
- Max result window: 10,000
- Mappings: file_name, page_number, content, page_embedding (768 dimensions)

### Ollama AI Models

| Variable | Default | Description |
|----------|---------|-------------|
| `OLLAMA_HOST` | `http://ollama:11434` | Ollama API endpoint |
| `OLLAMA_MODEL` | `qwen2.5:7b` | Translation model (4.7GB) |
| `OLLAMA_EMBEDDING_MODEL` | `nomic-embed-text` | Embedding model (274MB) |

**Alternative models:**
- Translation: `llama3.2:1b` (1.3GB, faster but less accurate)
- Embeddings: `mxbai-embed-large` (669M dimensions, slower)

**Auto-download:** Models download automatically via healthcheck during `make dev`.

### Symfony Messenger

| Variable | Dev Default | Prod Default | Description |
|----------|-------------|--------------|-------------|
| `MESSENGER_TRANSPORT_DSN` | `doctrine://default?auto_setup=0` | Same | Message queue transport |

**Alternative (Redis for prod):**
```env
MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages
```

**Worker configuration:**
- Dev: 3 workers, 3600s time limit, 256M memory
- Prod: 3 workers, 3600s time limit, 512M memory

### Web Server

| Variable | Dev Default | Prod Default | Description |
|----------|-------------|--------------|-------------|
| `APACHE_PORT` | `80` | `8080` | Apache listen port |

**Apache features:**
- HTTP/2 enabled
- Brotli compression (quality 6 dev, 11 prod)
- Gzip fallback
- Aggressive cache headers (1 year for versioned assets)
- Security headers (X-Frame-Options, X-Content-Type-Options, etc.)

### Docker Build

| Variable | Default | Description |
|----------|---------|-------------|
| `USER_ID` | `1000` | UID for file permissions |
| `GROUP_ID` | `1000` | GID for file permissions |

### Development Tools

| Variable | Default | Description |
|----------|---------|-------------|
| `XDEBUG_MODE` | `off` | Xdebug mode: `off`, `debug`, `coverage`, `profile` |

## File Hierarchy

### Environment Files

The project uses Symfony's standard pattern:

```
.env                  # Base config (committed, dev defaults)
.env.local            # Local dev overrides (NOT committed)
.env.prod             # Production config (committed)
.env.prod.local       # Production secrets (NOT committed, auto-generated)
```

**Loading order:**
1. `.env` (always)
2. `.env.local` (dev overrides)
3. `.env.$APP_ENV` (`.env.prod` for production)
4. `.env.$APP_ENV.local` (`.env.prod.local` for secrets)

### Docker Compose Files

```
docker-compose.yml          # Base (shared)
docker-compose.dev.yml      # Dev overrides
docker-compose.prod.yml     # Prod overrides
```

**Usage:**
```bash
# Dev (explicit)
docker compose -f docker-compose.yml -f docker-compose.dev.yml -p pdf-content-search up -d

# Prod (explicit)
docker compose -f docker-compose.yml -f docker-compose.prod.yml -p pdf-content-search-prod up -d
```

**Or use Makefile:**
```bash
make dev   # Handles file composition automatically
make prod  # Handles file composition automatically
```

## Advanced Configuration

### Custom Ports

Change Apache port to avoid conflicts:

```bash
# In .env.local (dev)
echo "APACHE_PORT=8080" >> .env.local

# In .env.prod.local (prod)
echo "APACHE_PORT=9090" >> .env.prod.local

# Restart
make restart
```

### Custom Database

Use external PostgreSQL:

```bash
# In .env.local
POSTGRES_HOST=external-db.example.com
POSTGRES_PORT=5432
POSTGRES_PASSWORD=secure-password

# Restart
make restart
```

### Custom Elasticsearch

Use external Elasticsearch:

```bash
# In .env.local
ELASTICSEARCH_HOST=https://es.example.com:9200
ELASTIC_PASSWORD=secure-password

# Restart
make restart
```

### Enable Xdebug

```bash
# In .env.local
XDEBUG_MODE=debug

# Restart
make restart
```

Configure your IDE to listen on port `9003`.

### Change Ollama Model

```bash
# In .env.local
OLLAMA_MODEL=llama3.2:1b  # Smaller, faster

# Restart (will auto-download new model)
make restart
```

### Increase PHP Memory

**Development:**
Edit `.docker/dev/php/php.ini`:
```ini
memory_limit = 1024M
```

**Production:**
Edit `.docker/prod/php/php.ini`:
```ini
memory_limit = 512M
```

Rebuild:
```bash
make rebuild-dev  # or rebuild-prod
```

### Custom Apache Config

**Development:**
```
.docker/dev/apache/apache.conf  # Main config
.docker/dev/apache/vhost.conf   # VirtualHost
```

**Production:**
```
.docker/prod/apache/apache.conf  # Main config + Brotli + HTTP/2
.docker/prod/apache/vhost.conf   # VirtualHost + preload hints
```

After changes, restart:
```bash
make restart  # or restart-prod
```

## Security Configuration

### Production Secrets

**Auto-generate:**
```bash
./bin/init-prod-secrets.sh
```

Creates `.env.prod.local` with:
- `APP_SECRET` (32-char hex)
- `POSTGRES_PASSWORD` (32-char hex)
- `ELASTIC_PASSWORD` (32-char hex)

**Manual generation:**
```bash
./bin/generate-secrets.sh 32  # Generate 32-char secret
```

### Elasticsearch Authentication

**Development:** Disabled by default (faster setup)

**Production:** Enabled automatically with `ELASTIC_PASSWORD`

Connection string:
```
http://elastic:${ELASTIC_PASSWORD}@elasticsearch:9200
```

### Environment Variable Validation

Production requires these variables (enforced with `?`):
- `APP_SECRET` (must be 32+ chars)
- `POSTGRES_PASSWORD`
- `ELASTIC_PASSWORD`

Startup fails if missing.

## Performance Tuning

### Messenger Workers

Adjust worker count in Supervisor config:

**Development:** `.docker/dev/supervisor/supervisord.conf`
```ini
[program:messenger-worker]
numprocs=3  # Change to 5 for more parallelism
```

**Production:** `.docker/prod/supervisor/supervisord.conf`
```ini
[program:messenger-worker]
numprocs=5  # Higher for production load
```

### Elasticsearch JVM Heap

Default: 512MB

**Increase for large datasets:**

Edit `docker-compose.prod.yml`:
```yaml
elasticsearch:
  environment:
    - "ES_JAVA_OPTS=-Xms1g -Xmx1g"  # 1GB heap
```

### Apache MPM

Edit `.docker/prod/apache/apache.conf`:
```apache
<IfModule mpm_event_module>
    ServerLimit         256
    StartServers        3
    MinSpareThreads     75
    MaxSpareThreads     250
    ThreadsPerChild     25
    MaxRequestWorkers   400
    MaxConnectionsPerChild  0
</IfModule>
```

## Logging

### Log Levels

**Development:**
```env
# In .env.local
APP_DEBUG=1  # Shows all errors + Symfony profiler
```

**Production:**
```env
# In .env.prod
APP_DEBUG=0  # Logs to files only
```

### View Logs

```bash
# All services
make logs

# Specific service
make logs SERVICE=php
make logs SERVICE=elasticsearch

# Follow mode
make logs-follow
```

### Log Files (inside containers)

```
/var/log/supervisor/          # Messenger workers
/var/log/apache2/             # Apache errors
/proc/self/fd/2               # PHP errors (stderr)
```

## Next Steps

- [Production Guide](production.md) - Deploy & optimization
- [Getting Started](getting-started.md) - Initial setup
- [Troubleshooting](troubleshooting.md) - Common issues
