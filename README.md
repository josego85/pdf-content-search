# PDF Content Search

[![PHP Version](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://www.php.net/)
[![Symfony Version](https://img.shields.io/badge/Symfony-7.2-green.svg)](https://symfony.com/)
[![Elasticsearch](https://img.shields.io/badge/Elasticsearch-8.12.1-005571.svg)](https://www.elastic.co/)
[![License: GPL-3.0](https://img.shields.io/badge/License-GPL%203.0-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

A Symfony application to search content within PDF files using Elasticsearch.

## Table of Contents
- [Description](#description)
- [Technologies](#technologies)
- [Requirements](#requirements)
- [Installation](#installation)
- [Docker Setup](#docker-setup)
- [Usage](#usage)
- [Contributing](#contributing)
- [License](#license)

## Description
This application allows users to search for content within PDF files using Elasticsearch for efficient text searching and indexing.

## Technologies
- PHP 8.4
- Composer 2.x
- Symfony 7.2
- Docker 27.5.1
- Elasticsearch 8.12.1

## Requirements
- Docker and Docker Compose
- PHP 8.4 or higher
- Composer 2.x
- pdftotext utility

## Installation
1. Clone the repository:
```bash
git clone git@github.com:yourusername/pdf-content-search.git
cd pdf-content-search
```

2. Install dependencies:
```bash
composer install
```

3. Install pdftotext utility:
```bash
sudo apt-get install poppler-utils
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

## Usage
1. Ensure you have PDF files in the `var/pdfs/` directory

2. Index the PDF files using the command:
```bash
php bin/console app:index-pdfs
```

3. Access the application at `http://localhost:8080`

4. Search functionalities:
   - Simple text search
   - Advanced filters
   - Date range filtering
   - Content highlighting

## Development
1. Start the development server:
```bash
symfony serve -d
```

2. Run tests:
```bash
php bin/phpunit
```

3. Check code style:
```bash
php-cs-fixer fix --dry-run
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