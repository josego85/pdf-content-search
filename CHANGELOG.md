# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.2] - 2025-04-09

### Code Style

- Improved PHP-CS-Fixer configuration:
  - Added full rule set aligned with PHP 8.3 best practices.
  - Removed dependency on external preset packages (e.g., `ergebnis/php-cs-fixer-config`).
  - Defined and enforced strict, explicit code style rules across the entire codebase.
- Applied PHP-CS-Fixer to refactor and standardize formatting across multiple PHP files.

### Tooling

- Added [Husky](https://typicode.github.io/husky) to enforce code style checks before commits.
- Configured a `pre-commit` Git hook to automatically run `composer cs-fix-check`.
- Commits are now blocked if PHP-CS-Fixer detects style violations, ensuring consistent formatting across the team.

## [1.2.1] - 2025-04-08

### Added
- Documentation Improvements:
  - Expanded README.md with detailed sections:
    - Features: Highlighting key functionalities like page-level PDF search, real-time results, and content highlighting.
    - Technologies: Comprehensive list of tools and frameworks used.
    - Requirements: Clear prerequisites for running the project.
    - Installation: Step-by-step guide for setting up the project.
    - Docker Setup: Instructions for building and running containers.
    - Configuration: Explanation of environment variables and service bindings.
    - PDF Management: Instructions for organizing and indexing PDFs.
    - Usage: Detailed guide on how to use the application.
    - Development: Added frontend and backend development workflows.
    - Elasticsearch: Commands for managing indices and monitoring cluster health.
    - Maintenance: Steps for clearing caches, updating dependencies, and rebuilding containers.
    - Troubleshooting: Common issues and solutions for Elasticsearch, frontend, and PDF indexing.
    - Security: Recommendations for securing the application in production.
    - Contributing: Guidelines for contributing to the project.

### Changed
- Refactored PDF indexing process:
  - Split PDFs into individual pages for better granularity.
  - Improved text extraction accuracy using `pdftotext`.
  - Enhanced metadata handling (e.g., total page count, file paths).
  - Improved error reporting for failed indexing operations.
- SearchController constructor refactoring:
  - Injected pdfPagesIndex from configuration instead of hardcoding the index name.
- Dockerfile cleanup:
  - Removed unused system package previously required for older workflows.
- PDF folder restructuring:
  - Changed location of indexed PDFs from var/pdfs/ to a more appropriate and web-accessible directory (public/pdfs/) for easier linking and access.

### Fixed
- Fixed Search Issues:
  - Resolved issue where similar words (e.g., "lose" instead of "Jose") were incorrectly highlighted.
  - Adjusted frontend logic to highlight only exact matches for search terms using regular expressions.
  - Enhanced backend query precision for Elasticsearch highlighting.
  - Fixed context display in search results for better readability.
- Addressed missing or unclear instructions in the README.md:
  - Added steps for verifying dependencies and services.
  - Clarified Docker commands for starting and stopping containers.
  - Included examples for debugging and troubleshooting common issues.
  
## [1.2.0] - 2025-04-08

### Added
- PDF Page-Level Search:
  - Individual page indexing for PDFs
  - Page content extraction with context
  - Page number tracking in search results
  - Direct PDF page links in results
- Enhanced Search Results:
  - Context snippets with highlighted matches
  - Page-specific navigation in PDFs
  - PDF preview integration in browser
  - Page count information display
- Command Improvements:
  - Page-by-page PDF processing
  - Unique ID generation per page
  - Better error handling per page
  - Progress indicators for indexing

### Changed
- Refactored PDF indexing process:
  - Split PDFs into individual pages
  - Improved text extraction accuracy
  - Enhanced metadata handling
  - Better error reporting
- Updated search interface:
  - Added page-specific result display
  - Improved result highlighting
  - Enhanced PDF viewer integration
  - Better result organization

### Fixed
- PDF page counting accuracy
- Text extraction reliability
- Search result context display
- PDF viewer integration issues
- Improved Content Highlighting:
  - Resolved issue where similar words (e.g., "lose" instead of "Jose") were incorrectly highlighted.
  - Adjusted frontend logic to highlight only exact matches for search terms.
  - Enhanced backend query to improve precision in highlighting.

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
- Development Tools:
  - Added PHP-CS-Fixer for code style enforcement
  - Configured Symfony and PSR-12 coding standards
  - Added composer scripts for style checking
  - VS Code integration setup
- Monitoring Tools:
  - Added Kibana 8.17.1 integration
  - Configured health checks for Kibana
  - Added Elasticsearch monitoring dashboard
  - Integrated with existing Elasticsearch setup

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