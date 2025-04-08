# PDF Content Search

[![Version](https://img.shields.io/badge/Version-1.1.0-blue.svg)](https://github.com/yourusername/pdf-content-search)
[![PHP Version](https://img.shields.io/badge/PHP-8.3-blue.svg)](https://www.php.net/)
[![Symfony Version](https://img.shields.io/badge/Symfony-7.2-green.svg)](https://symfony.com/)
[![Elasticsearch](https://img.shields.io/badge/Elasticsearch-8.17.1-005571.svg)](https://www.elastic.co/)
[![Kibana](https://img.shields.io/badge/Kibana-8.17.1-005571.svg)](https://www.elastic.co/kibana/)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.5.x-brightgreen.svg)](https://vuejs.org/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.4.x-38bdf8.svg)](https://tailwindcss.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791.svg)](https://www.postgresql.org/)
[![Node.js](https://img.shields.io/badge/Node.js-22.x-339933.svg)](https://nodejs.org/)
[![Docker](https://img.shields.io/badge/Docker-27.5.1-2496ED.svg)](https://www.docker.com/)
[![PHP-CS-Fixer](https://img.shields.io/badge/PHP--CS--Fixer-3.49-yellow.svg)](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer)
[![License: GPL-3.0](https://img.shields.io/badge/License-GPL%203.0-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)


A Symfony application to search content within PDF files using Elasticsearch and Vue.js.

## Table of Contents
- [Description](#description)
- [Technologies](#technologies)
- [Requirements](#requirements)
- [Installation](#installation)
- [Docker Setup](#docker-setup)
- [Usage](#usage)
- [Development](#development)
- [Elasticsearch](#elasticsearch)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

## Description
This application allows users to search for content within PDF files using Elasticsearch for efficient text searching and indexing, with a modern Vue.js frontend.

## Technologies
- PHP 8.3
- Symfony 7.2
- Elasticsearch 8.17.1
- Kibana 8.17.1
- Vue.js 3.5.x
- Tailwind CSS 3.4.x
- Docker 27.5.1 & Docker Compose
- Node.js 22.x
- PostgreSQL 16

## Requirements
- Docker 27.5.1 and Docker Compose
- PHP 8.3
- Composer 2.x
- Node.js 22.x and npm
- pdftotext utility (poppler-utils)

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

## Usage
1. Ensure you have PDF files in the `var/pdfs/` directory

2. Index the PDF files:
```bash
docker compose exec php bin/console app:index-pdfs
```

3. Access the application at `http://localhost`

4. Search Features:
   - Full-text search across PDF contents
   - Real-time search results
   - Content highlighting
   - Relevance scoring
   - Document metadata display
   - Fuzzy matching for typo tolerance

## Development
1. Start the development environment:
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

## Troubleshooting
1. Elasticsearch Issues:
```bash
# Check cluster health
docker compose exec elasticsearch curl -X GET "localhost:9200/_cluster/health"

# View logs
docker compose logs elasticsearch
```

2. Frontend Issues:
```bash
# Clear npm cache
npm cache clean --force

# Rebuild assets
npm run build
```

3. PDF Indexing Issues:
```bash
# Check PDF directory
ls var/pdfs/

# Run indexer in verbose mode
docker compose exec php bin/console app:index-pdfs -vv
```

## Contributing
1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License
This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.
For more information, please visit [GNU GPL v3](https://www.gnu.org/licenses/gpl-3.0.en.html).