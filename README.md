# PDF Content Search

[![Version](https://img.shields.io/badge/Version-1.3.1-blue.svg)](https://github.com/yourusername/pdf-content-search)
[![PHP Version](https://img.shields.io/badge/PHP-8.4.14-blue.svg)](https://www.php.net/)
[![Symfony Version](https://img.shields.io/badge/Symfony-7.3.6-green.svg)](https://symfony.com/)
[![Elasticsearch](https://img.shields.io/badge/Elasticsearch-8.17.10-005571.svg)](https://www.elastic.co/)
[![Kibana](https://img.shields.io/badge/Kibana-8.17.10-005571.svg)](https://www.elastic.co/kibana/)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.5.x-brightgreen.svg)](https://vuejs.org/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.4.x-38bdf8.svg)](https://tailwindcss.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791.svg)](https://www.postgresql.org/)
[![Node.js](https://img.shields.io/badge/Node.js-22.x-339933.svg)](https://nodejs.org/)
[![Docker](https://img.shields.io/badge/Docker-27.5.1-2496ED.svg)](https://www.docker.com/)
[![PHP-CS-Fixer](https://img.shields.io/badge/PHP--CS--Fixer-3.89.2-yellow.svg)](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer)
[![License: GPL-3.0](https://img.shields.io/badge/License-GPL%203.0-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

A Symfony application to search content within PDF files using Elasticsearch and Vue.js.

## Table of Contents
- [Features](#features)
- [Description](#description)
- [Technologies](#technologies)
- [Requirements](#requirements)
- [Installation](#installation)
- [Docker Setup](#docker-setup)
- [Configuration](#configuration)
- [PDF Management](#pdf-management)
- [Usage](#usage)
- [Development](#development)
- [Elasticsearch](#elasticsearch)
- [Maintenance](#maintenance)
- [Troubleshooting](#troubleshooting)
- [Security](#security)
- [Contributing](#contributing)
- [License](#license)

## Features
- 📄 Page-Level PDF Search
- 🔍 Real-time Search Results
- 🎯 Content Highlighting (Exact matches only)
- 📊 Relevance Scoring
- 📱 Responsive Design
- 🚀 Fast Elasticsearch Backend
- 🔄 Automatic PDF Processing
- 📋 Page Context Display
- 🔗 Direct PDF Page Links
- 📈 Search Analytics via Kibana

## Description
This application allows users to search for content within PDF files using Elasticsearch for efficient text searching and indexing, with a modern Vue.js frontend.

## Technologies
- PHP 8.4.14
- Symfony 7.3.2
- Elasticsearch 8.17.10
- Kibana 8.17.10
- Vue.js 3.5.x
- Tailwind CSS 3.4.x
- Docker 27.5.1 & Docker Compose
- Node.js 22.x
- PostgreSQL 16
- Apache 2.4

## Requirements
- Docker 27.5.1 and Docker Compose
- PHP 8.4.14
- Composer 2.x
- Node.js 22.x and npm
- pdftotext utility (poppler-utils)
- At least 4GB RAM (for Elasticsearch)

## Installation
1. Clone the repository:
```bash
git clone git@github.com:yourusername/pdf-content-search.git
cd pdf-content-search
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Install pdftotext utility:
```bash
sudo apt-get install poppler-utils
```

4. Build frontend assets:
```bash
npm run dev
```

## Docker Setup
1. Build and start the containers:
```bash
docker compose up -d --build
```

2. Verify containers are running:
```bash
docker compose ps
```

3. Access services:
- Application: http://localhost
- Elasticsearch: http://localhost:9200
- Kibana: http://localhost:5601
- PostgreSQL: localhost:5432

## Configuration
### Environment Variables
```dotenv
# PostgreSQL
POSTGRES_DB=app
POSTGRES_PASSWORD=!ChangeMe!
POSTGRES_USER=app
POSTGRES_VERSION=16

# Elasticsearch
ELASTICSEARCH_HOST=http://elasticsearch:9200
```

### Docker Services
- `apache`: HTTP Server (2.4)
- `php`: PHP-FPM 8.4.14
- `elasticsearch`: Search Engine (8.17.10)
- `kibana`: Analytics Dashboard (8.17.10)
- `database`: PostgreSQL 16

## PDF Management
1. Create PDF directories:
```bash
mkdir -p public/pdfs
```

2. Place your PDFs in `public/pdfs/`

3. Index the PDFs:
```bash
docker compose exec php bin/console app:index-pdfs
```

## Usage
1. Access the application at `http://localhost`
2. Use the search bar to find content in PDFs
3. Results will show:
   - PDF filename
   - Page number
   - Content context
   - Highlighted matches
   - Direct link to PDF page

## Development
1. Start development environment:
```bash
docker compose up -d
npm run watch
```

2. Run tests:
```bash
docker compose exec php bin/phpunit
```

3. Check code style:
```bash
# Check for violations without fixing
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run

# Check with detailed diff output
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix code style violations
docker compose exec php vendor/bin/php-cs-fixer fix
```

4. Frontend Development:
   - Components in `assets/components/`
   - Styles in `assets/css/`
   - Build: `npm run build`
   - Watch: `npm run watch`

## Elasticsearch
1. Check cluster health:
```bash
curl http://localhost:9200/_cluster/health
```

2. View indices:
```bash
curl http://localhost:9200/_cat/indices
```

3. Monitor with Kibana:
   - Access Kibana at http://localhost:5601
   - View index management
   - Monitor cluster health
   - Analyze search performance

## Maintenance
1. Clear caches:
```bash
docker compose exec php bin/console cache:clear
```

2. Update dependencies:
```bash
docker compose exec php composer update
docker compose exec php npm update
```

3. Rebuild containers:
```bash
docker compose down
docker compose build --no-cache
docker compose up -d
```

## Troubleshooting
1. Elasticsearch Issues:
```bash
# Check health
docker compose exec elasticsearch curl -X GET "localhost:9200/_cluster/health"
# View logs
docker compose logs elasticsearch
```

2. Frontend Issues:
```bash
# Clear cache
npm cache clean --force
# Rebuild
npm run build
```

3. PDF Indexing Issues:
```bash
# Check directory
ls public/pdfs/
# Verbose indexing
docker compose exec php bin/console app:index-pdfs -vv
```

## Security
- Change default PostgreSQL credentials
- Enable Elasticsearch security in production
- Configure HTTPS for production
- Set proper file permissions

## Contributing
1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## License
Licensed under GNU General Public License v3.0 - see [LICENSE](LICENSE) file.