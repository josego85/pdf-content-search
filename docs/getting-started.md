# Getting Started

Complete guide to get PDF Content Search running in 5 minutes.

## Prerequisites

- **Docker** & **Docker Compose** installed
- **Make** (pre-installed on Linux/macOS)
- **8GB RAM** minimum
- **10GB disk space** for Docker images + Ollama models

## Development Setup

### Quick Start (2 commands)

```bash
# 1. Clone repository
git clone https://github.com/josego85/pdf-content-search.git
cd pdf-content-search

# 2. Start everything
make dev
```

**Access:** http://localhost

That's it! The `make dev` command automatically:
- ✅ Builds Docker images
- ✅ Installs dependencies (Composer + NPM)
- ✅ Runs database migrations
- ✅ Creates Elasticsearch index
- ✅ Downloads Ollama models (qwen2.5:7b + nomic-embed-text)
- ✅ Builds frontend assets (Webpack)
- ✅ Starts 3 Messenger workers for async jobs

### What's Running

| Service | URL/Port | Description |
|---------|----------|-------------|
| **Web App** | http://localhost | Main application |
| **Analytics** | http://localhost/analytics | Search metrics dashboard |
| **Elasticsearch** | http://localhost:9200 | Search engine |
| **PostgreSQL** | localhost:5432 | Database |
| **Ollama** | http://localhost:11435 | AI models |

### Adding PDFs

```bash
# 1. Copy PDFs to public directory
cp /path/to/files/*.pdf public/pdfs/

# 2. Index them (with semantic embeddings)
docker compose -p pdf-content-search exec php php bin/console app:index-pdfs

# Skip embeddings for faster indexing (no AI search)
docker compose -p pdf-content-search exec php php bin/console app:index-pdfs --skip-embeddings
```

## Production Setup

### 1. Generate Secrets

```bash
./bin/init-prod-secrets.sh
```

This creates `.env.prod.local` with auto-generated:
- `APP_SECRET` (32-char hex)
- `POSTGRES_PASSWORD` (32-char hex)
- `ELASTIC_PASSWORD` (32-char hex)

**Important:** `.env.prod.local` is NOT committed to Git (contains secrets).

### 2. Start Production

```bash
make prod
```

**Access:** http://localhost:8080

**Differences from dev:**
- Port: 8080 (instead of 80)
- Debug: OFF
- Optimized PHP-FPM settings (512M memory, opcache)
- Multi-stage Docker build (smaller images)
- Elasticsearch authentication enabled
- Apache Brotli compression (quality 11)

## Common Commands

```bash
# Development
make dev           # Start development
make down          # Stop development
make restart       # Restart development
make logs          # View all logs
make logs SERVICE=php  # View specific service
make shell         # Open PHP container shell
make test          # Run PHPUnit tests

# Production
make prod          # Start production
make down-prod     # Stop production
make logs-prod     # View production logs
make shell-prod    # Open production shell

# Utilities
make status        # Show all environments status
make clean-dev     # Remove dev volumes (DESTRUCTIVE)
make clean-prod    # Remove prod volumes (DESTRUCTIVE)
make help          # Show all commands
```

## Environment Configuration

### Development (.env)

The `.env` file is **committed to Git** with safe defaults (Symfony standard):

```env
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=change-me-in-env-local  # OK for dev

POSTGRES_PASSWORD=dev_password      # OK for dev
ELASTICSEARCH_HOST=http://elasticsearch:9200  # No auth in dev
OLLAMA_MODEL=qwen2.5:7b
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
```

**For local overrides:** Create `.env.local` (not committed).

### Production (.env.prod + .env.prod.local)

**File hierarchy:**
1. `.env` (base config, committed)
2. `.env.prod` (production overrides, committed)
3. `.env.prod.local` (secrets, NOT committed, auto-generated)

**Production defaults** (`.env.prod`):
```env
APP_ENV=prod
APP_DEBUG=0
APACHE_PORT=8080
POSTGRES_DB=pdf_search_prod
ELASTICSEARCH_HOST=http://elastic:${ELASTIC_PASSWORD}@elasticsearch:9200
```

## Docker Architecture

### File Structure

```
docker-compose.yml          # Base (shared by dev & prod)
docker-compose.dev.yml      # Development overrides
docker-compose.prod.yml     # Production overrides

.docker/
├── dev/
│   ├── app/Dockerfile      # Dev PHP image
│   ├── apache/             # Apache config (Brotli quality 6)
│   └── supervisor/         # Messenger workers
└── prod/
    ├── app/Dockerfile      # Prod multi-stage build
    ├── apache/             # Apache config (Brotli quality 11)
    └── supervisor/         # Optimized workers
```

### Composition Pattern

**Development:**
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml -p pdf-content-search up -d
```

**Production:**
```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml -p pdf-content-search-prod up -d
```

**Why separate project names?**
- Prevents volume conflicts
- Can run dev + prod simultaneously (different ports)

## Troubleshooting

### Port 80 Already in Use

```bash
# Check what's using port 80
sudo lsof -i :80

# Change port in .env
echo "APACHE_PORT=8080" >> .env.local
make restart
```

### Elasticsearch Won't Start (Linux)

```bash
# Increase vm.max_map_count
sudo sysctl -w vm.max_map_count=262144

# Make permanent
echo 'vm.max_map_count=262144' | sudo tee -a /etc/sysctl.conf
```

### Docker Daemon Not Running

```bash
sudo systemctl start docker
```

### Ollama Models Not Downloading

Models download automatically via healthcheck, but if stuck:

```bash
# Check Ollama logs
make logs SERVICE=ollama

# Manual download
docker compose -p pdf-content-search exec ollama ollama pull qwen2.5:7b
docker compose -p pdf-content-search exec ollama ollama pull nomic-embed-text
```

### Messenger Workers Not Processing

```bash
# Check worker status
docker compose -p pdf-content-search exec php php bin/console messenger:stats

# Check Supervisor logs
make logs SERVICE=php | grep messenger

# Restart workers
docker compose -p pdf-content-search restart php
```

### Reset Everything (Nuclear Option)

```bash
# Stop and remove all data (DESTRUCTIVE)
make clean-dev

# Start fresh
make dev
```

### More Issues?

See [troubleshooting.md](troubleshooting.md) for complete guide.

## Next Steps

**Learn more:**
- [Configuration Guide](configuration.md) - Environment variables, advanced config
- [Production Guide](production.md) - Deploy, optimization, security
- [Analytics Dashboard](features/analytics.md) - Search metrics
- [REST API](features/api.md) - API endpoints
- [Translation](features/translation.md) - PDF translation with Ollama

**Development:**
- [Testing Guide](testing.md) - PHPUnit tests & coverage (87%)
- [Docker Architecture](reference/docker-architecture.md) - Internal details
- [Frontend Architecture](reference/frontend-architecture.md) - Webpack, Vue.js
