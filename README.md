# PDF Content Search

[![Version](https://img.shields.io/badge/Version-1.10.0-blue.svg)](https://github.com/josego85/pdf-content-search)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![Elasticsearch](https://img.shields.io/badge/Elasticsearch-9.2-005571?logo=elasticsearch&logoColor=white)](https://www.elastic.co/)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.5-4FC08D?logo=vue.js&logoColor=white)](https://vuejs.org/)
[![Ollama](https://img.shields.io/badge/Ollama-AI-000000?logo=ai&logoColor=white)](https://ollama.ai/)
[![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![Tests](https://img.shields.io/badge/Coverage-87%25-success?logo=phpunit&logoColor=white)](tests/)
[![License: GPL-3.0](https://img.shields.io/badge/License-GPL%203.0-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

AI-powered PDF search with hybrid semantic capabilities using Elasticsearch 9.2 vector search and Ollama embeddings.

## Features

- 🧠 **AI Hybrid Search** - Combines keyword matching with semantic understanding (RRF algorithm)
- 📄 Page-level PDF search with Elasticsearch 9.2 vector search
- 🔍 Multiple search modes: Hybrid AI, Exact match, Prefix match
- 🌍 AI-powered PDF translation (Ollama llama3.2)
- 🔄 Async job processing with Symfony Messenger
- 📊 **Analytics Dashboard** - Real-time search metrics with Vue.js + ApexCharts
- 📱 Responsive Vue.js frontend with in-PDF highlighting

## Quick Start

```bash
# Clone and start
git clone https://github.com/josego85/pdf-content-search.git
cd pdf-content-search
./docker-dev.sh up

# Build frontend assets
npm install
npm run build

# Setup Elasticsearch index
docker compose exec php php bin/console app:create-pdf-index

# Download Ollama models (required for AI features)
docker compose exec ollama ollama pull llama3.2:1b        # Translation model (~1.3GB)
docker compose exec ollama ollama pull nomic-embed-text   # Embedding model (~274MB)

# Add PDFs and index (with embeddings for semantic search)
cp your-pdfs/*.pdf public/pdfs/
docker compose exec php php bin/console app:index-pdfs

# Open: http://localhost
```

**📖 Full setup guide:** [docs/setup.md](docs/setup.md)

## Analytics Dashboard

Track search behavior, performance metrics, and user engagement in real-time.

```
http://localhost/analytics
```

**Features:**
- 📊 KPIs (searches, response time, success rate, sessions)
- 📈 Interactive charts (trends, strategy distribution, top queries)
- 🔍 Time filters (7/14/30/90 days)
- 🎯 REST API endpoints for custom integrations

**📖 Full analytics guide:** [docs/analytics.md](docs/analytics.md)

## Monitor Translations

```bash
./bin/monitor-jobs.sh --watch   # Real-time job tracking
./bin/worker-logs.sh -f         # Worker logs
```

## Stack

- **Backend:** PHP 8.4, Symfony 7.4, PostgreSQL 16
- **Search:** Elasticsearch 9.2 (vector search, HNSW)
- **Frontend:** Vue.js 3.5, Tailwind CSS 3.4, PDF.js 5.4, ApexCharts
- **AI:** Ollama (llama3.2 translations, nomic-embed-text embeddings)
- **Queue:** Symfony Messenger (3 workers)
- **Analytics:** PostgreSQL 16 (metrics storage), Vue.js dashboard

## Documentation

See [`docs/`](docs/):
- [setup.md](docs/setup.md) - Installation and configuration
- [analytics.md](docs/analytics.md) - Analytics dashboard guide
- [api.md](docs/api.md) - REST API reference
- [troubleshooting.md](docs/troubleshooting.md) - Common issues and solutions
- [translation-tracking.md](docs/translation-tracking.md) - Job tracking system
- [messenger-worker.md](docs/messenger-worker.md) - Async workers
- [frontend.md](docs/frontend.md) - Frontend architecture
- [docker.md](docs/docker.md) - Docker details
- [testing.md](docs/testing.md) - Tests

## Development vs Production

```bash
# Development (port 80)
./docker-dev.sh up     # Start with migrations
./docker-dev.sh logs   # View logs
./docker-dev.sh down   # Stop

# Production (port 8080)
./docker-prod.sh up    # Start with migrations
./docker-prod.sh logs  # View logs
./docker-prod.sh down  # Stop
```

**📖 Docker guide:** [docs/docker.md](docs/docker.md)

## Development

```bash
# Backend
docker compose exec php composer install
docker compose exec php php bin/console cache:clear

# Frontend
npm install
npm run dev

# Tests
docker compose exec php php bin/phpunit
```

## License

Licensed under [GNU General Public License v3.0](LICENSE).
