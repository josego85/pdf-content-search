# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-04-08

### Added
- Created `SearchEngineInterface` for search service abstraction
- Improved error handling in Elasticsearch operations
- Added type hints and return types for better code clarity
- Frontend Search Implementation:
  - Vue.js search component with real-time feedback
  - Tailwind CSS styling and responsive design
  - Search results highlighting
  - Loading states and error handling
  - Debounced search functionality
  - Document metadata display (date, score)

### Changed
- Refactored `ElasticsearchService` to implement `SearchEngineInterface`
- Improved Elasticsearch client configuration
- Enhanced exception handling for Elasticsearch operations

## [1.0.0] - 2025-04-08

### Added
- Initial project setup with Symfony 7.2
- Docker infrastructure:
  - PostgreSQL 16 with health checks
  - Apache 2.4 web server
  - PHP-FPM 8.4 configuration
  - Elasticsearch 8.17.1 integration
- Basic project configuration:
  - Docker Compose setup
  - Environment variables structure
  - Project documentation
- Elasticsearch features:
  - Health checks implementation
  - Volume persistence
  - Memory optimization
  - Security configuration
- Apache and PHP integration
- Database configuration and persistence
- Console Commands:
  - PDF indexer command (`app:index-pdfs`)
  - Automatic text extraction from PDFs
  - Elasticsearch document indexing
- Documentation:
  - Comprehensive README.md with:
    - Project description
    - Installation instructions
    - Docker setup guide
    - Usage examples
    - Development guidelines
    - Contributing guidelines
    - License information

### Changed
- N/A

### Deprecated
- N/A

### Removed
- N/A

### Fixed
- N/A

### Security
- Disabled Elasticsearch security for development
- Basic authentication setup for services