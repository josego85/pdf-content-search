# PDF Content Search

[![Version](https://img.shields.io/badge/Version-1.8.0-blue.svg)](https://github.com/josego85/pdf-content-search)
[![PHP](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.4-green.svg)](https://symfony.com/)
[![License: GPL-3.0](https://img.shields.io/badge/License-GPL%203.0-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

Search content within PDF files using Elasticsearch and Vue.js.

## Features

- 📄 Page-level PDF search with Elasticsearch
- 🔍 Real-time search with intelligent highlighting
- 🌍 AI-powered PDF translation (Ollama)
- 🔄 Async job processing with Symfony Messenger
- 📊 Search analytics via Kibana
- 📱 Responsive Vue.js frontend

## Quick Start

```bash
# Clone and start
git clone https://github.com/josego85/pdf-content-search.git
cd pdf-content-search
docker compose up -d

# Add PDFs and index
cp your-pdfs/*.pdf public/pdfs/
docker compose exec php php bin/console app:index-pdfs

# Open: http://localhost
```

## Monitor Translations

```bash
./bin/monitor-jobs.sh --watch   # Real-time job tracking
./bin/worker-logs.sh -f         # Worker logs
```

## Stack

- **Backend:** PHP 8.4, Symfony 7.4, PostgreSQL 16
- **Search:** Elasticsearch 8.17, Kibana 8.17
- **Frontend:** Vue.js 3.5, Tailwind CSS 3.4, PDF.js 5.4
- **AI:** Ollama (translations)
- **Queue:** Symfony Messenger (3 workers)

## Documentation

See [`docs/`](docs/):
- [setup.md](docs/setup.md) - Installation and configuration
- [translation-tracking.md](docs/translation-tracking.md) - Job tracking system
- [messenger-worker.md](docs/messenger-worker.md) - Async workers
- [frontend.md](docs/frontend.md) - Frontend architecture
- [docker.md](docs/docker.md) - Docker details
- [testing.md](docs/testing.md) - Tests

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
