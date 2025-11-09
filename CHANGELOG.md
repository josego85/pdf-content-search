# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Comprehensive CI/CD Pipeline**:
  - **CI Workflow**: Automated testing, code style checks, and frontend build validation
    - PHPUnit tests for PHP 8.4
    - PHP-CS-Fixer code style validation
    - Frontend asset build verification
    - Composer dependency caching for faster builds
    - npm caching for optimized workflow performance
  - **CodeQL Security Analysis**: Automated static code analysis for JavaScript/TypeScript
    - Scheduled weekly security scans every Monday at 6:00 AM UTC
    - Runs on push to main/develop branches and pull requests
    - Security vulnerability detection for JavaScript codebase
  - **Security Audit Workflow**: Comprehensive dependency vulnerability scanning
    - npm audit for JavaScript dependencies (moderate severity threshold)
    - Composer audit for PHP dependencies
    - Dependency Review action for pull requests
    - Daily scheduled security audits at 2:00 AM UTC
    - Audit reports uploaded as artifacts with 30-day retention
    - Manual trigger capability via workflow_dispatch
  - **Dependabot Configuration**: Automated dependency updates
    - Weekly updates for Composer (PHP) dependencies every Monday
    - Weekly updates for npm (JavaScript) dependencies every Monday
    - Weekly updates for GitHub Actions
    - Docker base image updates
    - Grouped updates for Symfony, Babel, and Webpack ecosystems
    - Intelligent major version ignoring for critical packages (Vue.js, Webpack)
    - Automatic PR creation with proper labels and conventional commit messages
  - **CI/CD Badges**: Added workflow status badges to README
    - CI build status
    - CodeQL security analysis status
    - Security audit status
- **Composite Actions for Code Reusability**:
  - **setup-php-project**: Reusable action for PHP project setup
    - Configures PHP with specified version and extensions
    - Implements Composer dependency caching
    - Installs dependencies automatically
    - Supports customization via inputs (php-version, extensions, tools)
  - **setup-node-project**: Reusable action for Node.js project setup
    - Configures Node.js with specified version
    - Implements npm caching automatically
    - Installs dependencies with npm ci
    - Supports customization via inputs (node-version)

### Changed
- **Workflow Triggers**: Enhanced workflow execution triggers to run on feature branches
  - All workflows now trigger on `main`, `develop`, `feature/**`, and `claude/**` branches
  - Enables CI/CD testing during feature development before PR creation
  - Maintains PR-based triggers for main/develop branches
  - Improves feedback loop for developers working on feature branches
- **PHP Tests Job**: Temporarily disabled in CI workflow
  - Job configured but not executed (`if: false`)
  - Easy to re-enable when tests are implemented
  - Code style and frontend build checks remain active
- **Workflow Architecture Refactoring**: Major DRY improvements
  - Eliminated ~60% code duplication across workflows
  - Reduced workflow complexity from 250 to ~150 lines total
  - Centralized PHP/Node.js setup logic in composite actions
  - Single source of truth for dependency management
  - Easier maintenance: Update SHA in one place vs seven places
  - Improved readability: Focus on business logic vs boilerplate

### Security
- **Pinned GitHub Actions to SHA**: All workflow actions now use commit SHA instead of tags for supply chain attack prevention
  - `actions/checkout@v5.0.0` → SHA `71cf2267d89c5cb81562390fa70a37fa40b1305e`
  - `shivammathur/setup-php@v2.31.1` → SHA `c541c155eee45413f5b09a52248675b1a2575231`
  - `actions/cache@v4.2.0` → SHA `1bd1e32a3bdc45362d1e726936510720a7c30a57`
  - `actions/setup-node@v4.0.3` → SHA `1e60f620b9541d16bece96c5465dc8ee9832be0b`
  - `actions/upload-artifact@v5.0.0` → SHA `330a01c490aca151604b8cf639adc76d48f6c5d4`
  - `github/codeql-action/*@v4.31.2` → SHA `0499de31b99561a6d14a36a5f662c2a54f91beee`
  - `actions/dependency-review-action@v4.8.1` → SHA `40c09b7dc99638e5ddb0bfd91c1673effc064d8a`
  - Comments with version tags maintained for reference and easier updates

## [1.5.0] - 2025-11-08

### Added
- **Professional Search UI**:
  - Modern gradient background design (gray-50 → blue-50 → gray-100)
  - Hero section with centered icon and professional typography
  - Enhanced search box with keyboard shortcuts (/ to focus, ESC to clear)
  - Search performance metrics display (result count and duration in ms)
  - Grid/List view toggle for search results
  - Favorites system with localStorage persistence
  - Professional loading states with dual-ring spinner
  - Improved empty states with actionable suggestions
  - Initial state showing feature benefits (Lightning Fast, Smart Highlighting, In-Page Highlighting)

### Changed
- **Comprehensive Responsive Design Implementation**:
  - **CRITICAL FIX**: Added viewport meta tag to `base.html.twig` (essential for proper mobile rendering)
  - **Mobile-First Strategy**: Implemented progressive enhancement from mobile (320px) → tablet (640px+) → desktop (1024px+)
  - **Responsive Breakpoints**:
    - Mobile (default): 0-640px - Single column layout, compact UI, essential features
    - Tablet (sm): 640px+ - Two column grid, medium spacing, expanded features
    - Desktop (md/lg): 768px+ - Full feature set, maximum spacing, complete text labels
  - **Touch Optimization**:
    - All interactive elements meet 44x44px minimum touch target size
    - Added `touch-manipulation` CSS for better mobile interaction
    - Improved button and control sizes for tablet/mobile devices
  - **Typography Scaling**:
    - Hero title: `text-3xl` (mobile) → `sm:text-4xl` (tablet) → `md:text-5xl` (desktop)
    - Search input: `py-3` (mobile) → `sm:py-4` (tablet) → `md:py-5` (desktop)
    - All text elements scale progressively across breakpoints
  - **Component-Specific Improvements**:
    - **Search Container**: Responsive padding `px-4 sm:px-6 lg:px-8`, `py-6 sm:py-8 md:py-12`
    - **Hero**: Scaled icons and text with horizontal padding to prevent clipping
    - **Search Bar**: Optimized input padding, simplified placeholder for mobile, responsive clear button
    - **Controls**: Conditional text display (hide verbose labels on mobile), responsive icons
    - **Results Grid**: Smart breakpoints `grid-cols-1 sm:grid-cols-2`, progressive gap sizing
    - **Result Cards**: Compact badges on mobile, icon-only "View PDF" button on small screens, improved text truncation
    - **State Components**: All loading, empty, error, and initial states fully responsive
  - **Layout Optimizations**:
    - Progressive spacing: smaller margins/padding on mobile, larger on desktop
    - Responsive border radius: `rounded-xl sm:rounded-2xl`
    - Flexible grid layouts with proper breakpoint transitions
  - **Accessibility Enhancements**:
    - Added ARIA labels to all interactive buttons
    - Improved semantic HTML with `lang="en"` attribute
    - Better keyboard navigation support
    - Enhanced screen reader compatibility
  - **Technical Improvements**:
    - Added `flex-shrink-0` to prevent unwanted layout collapse
    - Used `break-words` for proper long text handling
    - Implemented `min-w-0` for correct flexbox text truncation
    - Replaced `space-x` with `gap` utilities for better mobile support
    - All interactive states include `active:` pseudo-classes for touch feedback
- **Modular Component Architecture (SOLID Principles)**:
  - Refactored `SearchComponent.vue` (440 lines) into 9 specialized components
  - Applied Single Responsibility Principle for better maintainability
  - Component structure: `search/Search.vue` with `Hero`, `Bar`, `Controls`, `Results`, `ResultCard`, and 4 state components
- **Vue.js Optimization**:
  - Enabled runtime-only build (~33KB bundle size reduction)
  - Updated `webpack.config.js` with `runtimeCompilerBuild: false`
  - Migrated from DOM template compilation to direct component mounting
  - Simplified `templates/search.html.twig` (removed `<search-component>` tag)
- **Component Naming Convention**:
  - Adopted Vue 3 Style Guide enterprise conventions
  - Path-based naming: components named by context, not redundant prefixes
  - Cleaner imports: `import Hero from './Hero.vue'` vs `import SearchHero from './SearchHero.vue'`

### Removed
- Monolithic `SearchComponent.vue` replaced by modular architecture

## [1.4.0] - 2025-11-08

### Added
- **PDF Highlighting System**:
  - Intelligent hybrid word boundary detection for accurate highlighting
  - Automatic detection of malformed PDF text layers (words without spaces)
  - Context-aware matching: strict word boundaries for normal text, permissive for malformed PDFs
  - Support for special characters in word boundaries (bullets, em-dashes, etc.)
  - Position mapping system for precise character-level highlighting across normalized and original text
- **Search Architecture (SOLID)**:
  - `QueryBuilderInterface` contract for search engine abstraction
  - `SearchStrategy` enum (HYBRID, EXACT, PREFIX) for configurable search behavior
  - `QueryParser` service for advanced search operators (`"quotes"`, `+required`, `-exclude`)
  - `SearchQueryBuilder` with intelligent hybrid search: exact matches prioritized, fuzzy only for 5+ char words
- **Docker Infrastructure**:
  - Multi-stage Docker setup (development and production)
  - Alpine-based images for 71% size reduction (525MB dev, ~250MB prod vs 1.82GB)
  - Separate Dockerfiles for dev and prod environments
  - `.dockerignore` for optimized builds
  - Comprehensive Docker documentation in `docs/docker.md`

### Changed
- **PDF.js Upgrade**:
  - Upgraded PDF.js from v2.16.105 (2022) to v5.4.394 (2025)
  - Migrated to modern PDF.js v5 API (`TextLayer` class instead of `TextLayerBuilder`)
  - Updated webpack configuration to copy `.mjs` worker files for PDF.js v5
  - Improved text layer rendering with better spacing and positioning
- **PDF Highlighting**:
  - Refactored highlighting algorithm to mark all occurrences (previously only first occurrence)
  - Implemented word boundary validation to prevent false matches (e.g., "java" in "javascript")
  - Uses ultra-minimal CSS with `all: unset` to prevent text duplication
  - Highlight color changed to soft yellow (#fef3c7) matching search results preview
  - Text rendered on canvas with transparent text layer overlay for clean highlighting
  - Removed debugging console.log statements for production-ready code
- **Search Logic**:
  - Refactored search to prioritize exact matches (10x boost), then word matches (5x), then fuzzy (1x)
  - Fixes issue where "jos" incorrectly matched "job" - now only exact or close matches
  - `SearchController` now depends on `QueryBuilderInterface` (Dependency Inversion Principle)
- **Docker Configuration**:
  - Migrated from Debian to Alpine Linux base images
  - Reorganized Docker files: `.docker/dev/` and `.docker/prod/` structure
  - Renamed `compose.yaml` to `docker-compose.yml` (production base)
  - `docker-compose.override.yml` auto-loaded for development
  - Apache and PHP configs moved to `.docker/dev/` subdirectories
- **Documentation**:
  - Moved Docker documentation from README to `docs/docker.md`
  - Simplified README with link to detailed Docker docs
- **build:** Updated PHP from version 8.4.11 to 8.4.14
- **build:** Updated ElasticSearch from version 8.17.1 to 8.17.10
- **build:** Updated Kibana from version 8.17.1 to 8.17.10
- **build(deps):** Updated Composer dependencies to latest compatible versions
- **build(deps):** Updated npm dependencies:
  - Vue.js from 3.5.13 to 3.5.24
  - @vue/compiler-sfc from 3.5.13 to 3.5.24
  - postcss from 8.5.3 to 8.5.6
  - Fixed dependency versions (removed ^ ranges) for reproducible builds
  - Fixed 2 low severity npm vulnerabilities (brace-expansion, tmp)

### Removed
- Root `Dockerfile` in favor of organized `.docker/dev/` and `.docker/prod/` structure
- `compose.override.yaml` replaced by `docker-compose.override.yml`
- Makefile commands (using standard `docker-compose` commands)

### Fixed
- **PDF Highlighting Issues**:
  - Fixed text duplication/overlapping in PDF viewer caused by visible text in both canvas and text layer
  - Corrected word boundary detection to properly skip compound words like "javascript" when searching "java"
  - Fixed highlighting to find all occurrences instead of just the first one per span
  - Resolved issues with highlighting words containing accents (e.g., "José" when searching "jose")
  - Fixed text layer dimensions to match viewport size in PDF.js v5
- Elasticsearch single-node configuration (`cluster.routing.allocation.disk.threshold_enabled=false`)
- `ElasticsearchService::deleteIndex()` now checks index existence before deletion

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