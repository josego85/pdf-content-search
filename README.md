# PDF Content Search

[![Version](https://img.shields.io/badge/Version-1.8.1-blue.svg)](https://github.com/josego85/pdf-content-search)
[![PHP](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.4-green.svg)](https://symfony.com/)
[![License: GPL-3.0](https://img.shields.io/badge/License-GPL%203.0-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

Search content within PDF files using Elasticsearch and Vue.js.

## Features

- 🧠 **AI Hybrid Search** - Combines keyword matching with semantic understanding (RRF algorithm)
- 📄 Page-level PDF search with Elasticsearch 9.2 vector search
- 🔍 Multiple search modes: Hybrid AI, Exact match, Prefix match
- 🌍 AI-powered PDF translation (Ollama llama3.2)
- 🔄 Async job processing with Symfony Messenger
- 📊 Search analytics via Kibana
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

## Monitor Translations

```bash
./bin/monitor-jobs.sh --watch   # Real-time job tracking
./bin/worker-logs.sh -f         # Worker logs
```

## Stack

- **Backend:** PHP 8.4, Symfony 7.4, PostgreSQL 16
- **Search:** Elasticsearch 9.2 (vector search, HNSW), Kibana 9.2
- **Frontend:** Vue.js 3.5, Tailwind CSS 3.4, PDF.js 5.4
- **AI:** Ollama (llama3.2 translations, nomic-embed-text embeddings)
- **Queue:** Symfony Messenger (3 workers)

## Documentation

See [`docs/`](docs/):
- [setup.md](docs/setup.md) - Installation and configuration
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
