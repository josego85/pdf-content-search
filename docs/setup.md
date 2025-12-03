# Setup Guide

Complete installation and configuration guide.

## Requirements

- Docker 27.5+ and Docker Compose
- At least 4GB RAM (for Elasticsearch)
- Git

**Optional (for local development without Docker):**
- PHP 8.4+
- Composer 2.x
- Node.js 22.x
- PostgreSQL 16
- pdftotext utility (poppler-utils)

## Installation

### 1. Clone Repository

```bash
git clone https://github.com/josego85/pdf-content-search.git
cd pdf-content-search
```

### 2. Environment Setup

```bash
cp .env .env.local
# Edit .env.local if needed (ports, credentials, etc)
```

### 3. Start Services

```bash
docker compose up -d
```

**Wait for services to be healthy:**
```bash
docker compose ps
# All services should show "healthy" or "Up"
```

### 4. Install Dependencies

```bash
# Backend
docker compose exec php composer install

# Frontend
npm install
npm run build
```

### 5. Database Setup

```bash
# Run migrations
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### 6. Elasticsearch Setup

```bash
# Create PDF index with correct mappings
docker compose exec php php bin/console app:create-pdf-index
```

### 7. Ollama Model Setup

**Required for AI translations**

```bash
# Download the translation model (llama3.2:1b ~1.3GB)
docker compose exec ollama ollama pull llama3.2:1b

# Verify model is downloaded
docker compose exec ollama ollama list
```

**⏱️ Note:** First download takes ~3 minutes depending on your connection.

### 8. Add PDFs and Index

```bash
# Copy your PDFs to the public directory
cp /path/to/your/pdfs/*.pdf public/pdfs/

# Index all PDFs into Elasticsearch
docker compose exec php php bin/console app:index-pdfs
```

### 9. Verify

Open http://localhost and search for content in your PDFs.

## Configuration

### Elasticsearch

Edit `config/packages/elasticsearch.yaml`:
```yaml
elasticsearch:
    hosts: ['%env(ELASTICSEARCH_URL)%']
```

Set in `.env.local`:
```bash
ELASTICSEARCH_URL=http://elasticsearch:9200
```

### Messenger Workers

Workers are configured in `config/packages/messenger.yaml`:
```yaml
async:
    dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
    options:
        check_delayed_interval: 1000
        redeliver_timeout: 300

retry_strategy:
    max_retries: 3
    delay: 10000
    multiplier: 2
```

3 workers run automatically via supervisor (see `docker-compose.override.yml`).

### Ollama (AI Translation)

Configure in `.env.local`:
```bash
OLLAMA_HOST=http://ollama:11434
OLLAMA_MODEL=llama3.2:3b
```

## Ports

Default ports (configure in `.env.local` if needed):

| Service | Port | URL |
|---------|------|-----|
| Web | 80 | http://localhost |
| Elasticsearch | 9200 | http://localhost:9200 |
| Kibana | 5601 | http://localhost:5601 |
| PostgreSQL | 5432 | localhost:5432 |
| Ollama | 11435 | http://localhost:11435 |

## Troubleshooting

### Elasticsearch Not Starting

Check logs:
```bash
docker compose logs elasticsearch
```

Common fix - increase vm.max_map_count:
```bash
sudo sysctl -w vm.max_map_count=262144
```

### Permission Errors

Fix file permissions:
```bash
./bin/fix-permissions.sh
```

### Workers Not Processing

Check workers are running:
```bash
./bin/worker-logs.sh
```

Should show 3 active workers.

### Frontend Not Updating

Clear browser cache or disable cache in DevTools.

Rebuild assets:
```bash
npm run build
```

## Next Steps

- Read [translation-tracking.md](translation-tracking.md) to monitor translations
- See [messenger-worker.md](messenger-worker.md) for worker configuration
- Check [testing.md](testing.md) to run tests
