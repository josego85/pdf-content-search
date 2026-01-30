# PDF Content Search

[![Version](https://img.shields.io/badge/Version-1.11.1-blue.svg)](https://github.com/josego85/pdf-content-search)
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
- 🌍 AI-powered PDF translation (Ollama qwen2.5)
- 🔄 Async job processing with Symfony Messenger
- 📊 **Analytics Dashboard** - Real-time search metrics with Vue.js + ApexCharts
- 📱 Responsive Vue.js frontend with in-PDF highlighting

## Quick Start

```bash
# 1. Clone and start
git clone https://github.com/josego85/pdf-content-search.git
cd pdf-content-search
make dev

# 2. Add PDFs and index them
cp your-pdfs/*.pdf public/pdfs/
docker compose -p pdf-content-search exec php php bin/console app:index-pdfs

# 3. Open: http://localhost
```

**What `make dev` does automatically:**
- ✅ Installs dependencies (Composer + NPM)
- ✅ Runs database migrations
- ✅ Creates Elasticsearch index structure
- ✅ Downloads Ollama models (qwen2.5:7b + nomic-embed-text)
- ✅ Builds frontend assets

> **Note:** `.env` is committed with safe defaults (Symfony standard).

## Common Commands

```bash
make help          # Show all available commands
make dev           # Start development (http://localhost)
make prod          # Start production (http://localhost:8080)
make down          # Stop environment
make logs          # View logs (add SERVICE=php for specific service)
make shell         # Open shell in PHP container
make test          # Run PHPUnit tests (87% coverage)
make status        # Show all environments status

# Translation monitoring (helper scripts)
./bin/monitor-jobs.sh --watch   # Real-time job tracking
./bin/worker-logs.sh -f         # Worker logs

# Translation monitoring (full commands)
docker compose -p pdf-content-search exec php php bin/console app:translation:monitor        # Active jobs
docker compose -p pdf-content-search exec php php bin/console app:translation:monitor --all  # All jobs
docker compose -p pdf-content-search exec php php bin/console app:translation:monitor --watch # Watch mode
```

## Stack

- **Backend:** PHP 8.4, Symfony 7.4, PostgreSQL 16
- **Search:** Elasticsearch 9.2 (vector search, HNSW)
- **Frontend:** Vue.js 3.5, Tailwind CSS 3.4, PDF.js 5.4, ApexCharts
- **AI:** Ollama (qwen2.5 translations, nomic-embed-text embeddings)
- **Queue:** Symfony Messenger (3 workers)
- **Analytics:** PostgreSQL 16 (metrics storage), Vue.js dashboard

## Documentation

### Getting Started
- [Getting Started](docs/getting-started.md) - Complete setup in 5 minutes
- [Configuration](docs/configuration.md) - Environment variables & advanced settings
- [Production](docs/production.md) - Deploy, optimization & security
- [Testing](docs/testing.md) - PHPUnit tests & coverage (87%)
- [Troubleshooting](docs/troubleshooting.md) - Common issues & solutions

### Features
- [Analytics Dashboard](docs/features/analytics.md) - Search metrics & KPIs (http://localhost/analytics)
- [REST API](docs/features/api.md) - API reference & endpoints
- [PDF Translation](docs/features/translation.md) - Ollama translation & job tracking

### Reference
- [Frontend Architecture](docs/reference/frontend-architecture.md) - Webpack, Vue.js, Tailwind
- [Security](SECURITY-FIXES-APPLIED.md) - Security status (8.0/10 - Production ready)

## License

Licensed under [GNU General Public License v3.0](LICENSE).
