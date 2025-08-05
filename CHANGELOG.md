# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.1] - 2025-08-05

### Added
- Support for the PHP `intl` extension to enhance internationalization features and improve overall performance.

### Changed
- **build:** Upgraded Symfony from version 7.2 to 7.3.
- **build:** Upgraded PHP from version 8.3 to 8.4.
- **docs:** Updated application version badge in `README.md`.

### Fixed
- Missing `intl` PHP extension warning in Symfony during runtime.

## [1.3.0] - 2025-08-05

### Added

- **PDF Viewer Integration**:
  - Added a new PDF viewer route (`/viewer`) that allows users to open a PDF document at a specific page using `?path=...&page=...`.
  - Integrated [PDF.js](https://mozilla.github.io/pdf.js/) from Mozilla to render PDF pages directly in the browser using a `<canvas>` and a dynamic text layer.
  - Implemented search term highlighting for a given query using `?q=...`, applied on the specified page.
  - Highlighting is case-insensitive and styled using `<mark>` elements injected into the text layer.
  - The highlight feature now retrieves terms directly from the **Elasticsearch results** (search highlights), enabling a more seamless experience when navigating between results.
  - Added parsing and injection of the highlighted terms into the PDF viewer dynamically, improving the user experience.
  - **Limitations**: Currently highlights only the **first occurrence** of the search term per span (this limitation will be improved in future versions).
  - **Improved the highlight feature to correctly show all matches including those with accented characters, fixing issues where accented terms were partially or incorrectly highlighted.**

- **Project Management**:
  - Added `TODO.md` document to track pending features, improvements, and technical debt.
  - Serves as a lightweight roadmap for contributors and team members.

- **Accent and Special Character Normalization**:
  - The indexer now replaces accented characters and special variations of vowels (e.g., á, é, í, ó, ú, ü) with their plain equivalents (a, e, i, o, u) during indexing.
  - This improves the consistency of search queries and results when users omit accents.

### Changed

- **Indexer and Search Refactoring**:
  - Major refactor of the indexer and search logic to improve maintainability and search consistency.
  - Normalized input during both indexing and querying phases to better handle special characters and improve match accuracy.

- **Search Results Handling**:
  - The highlight terms fetched from Elasticsearch are now processed and passed to the PDF viewer for more accurate highlighting.
  - The search terms in Elasticsearch are parsed to ensure they are appropriately reflected in the PDF viewer.
  - Enhanced handling of search results to integrate smoothly with the PDF viewer.

### Known Issues

- **Character Encoding**:
  - There may be issues with certain characters (e.g., accented characters) not being properly highlighted in the PDF viewer. This issue will be addressed in future versions.
  - The rendering of accented characters such as `José` in the highlights might not be perfect due to encoding differences between the PDF content and the search terms.

## [1.2.2] - 2025-04-09

### Added

- `.php-cs-fixer.cache` added to `.gitignore` to avoid committing temporary fixer cache files.
- Code Style Enforcement:
  - Introduced [Husky](https://typicode.github.io/husky) to run style checks automatically before each commit.
  - Configured a `pre-commit` Git hook to run `composer cs-check` and block commits if style violations are detected.
- `.editorconfig` added to enforce consistent formatting across editors:
  - Enforces 4-space indentation for PHP, 2 spaces for YAML, JSON, and JS files.
  - Uses LF line endings and trims trailing whitespace.
  - Ensures consistent newline endings and UTF-8 encoding.

### Changed

- Enhanced PHP-CS-Fixer configuration:
  - Added full rule set aligned with PHP 8.3 best practices.
  - Enforced stricter and explicit code style across the codebase.
- Applied PHP-CS-Fixer rules to refactor and reformat multiple PHP files for consistency.

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
  - PHP-FPM 8.3 configuration
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