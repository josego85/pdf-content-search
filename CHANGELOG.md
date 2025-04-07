CHANGELOG.md
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-04-07

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