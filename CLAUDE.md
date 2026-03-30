# CLAUDE.md — PDF Content Search

This file provides authoritative context for AI assistants (Claude Code) working in this repository.
Read this before making any changes. Follow every instruction precisely.

---

## Project Overview

**PDF Content Search** is a production-grade, AI-powered PDF search application.

- **Backend:** PHP 8.4 / Symfony 7.4
- **Frontend:** Vue.js 3.5 / Tailwind CSS 3.4 / Webpack Encore
- **Database:** PostgreSQL 16
- **Search Engine:** Elasticsearch 9.3 (hybrid lexical + semantic / kNN vector)
- **AI/ML:** Ollama — `qwen2.5:7b` (translation), `nomic-embed-text` (embeddings, 768d)
- **Queue:** Symfony Messenger + Doctrine transport
- **Containerization:** Docker (multi-stage builds, Alpine Linux)

---

## Repository Structure

```
pdf-content-search/
├── .docker/           # Dockerfile configs (dev & prod), Apache, Supervisor, PHP ini
├── .github/workflows/ # CI/CD: ci.yml, security-audit.yml, codeql.yml
├── assets/            # Vue.js components, Tailwind CSS, Webpack entry points
│   └── components/
│       ├── search/    # Search UI components (Search.vue, Bar.vue, Results.vue, etc.)
│       └── analytics/ # Analytics dashboard components (KPICard, charts, export)
├── bin/               # Shell scripts (monitor-jobs.sh, worker-logs.sh, etc.)
├── config/            # Symfony config (services.yaml, routes, packages/)
├── docs/              # Architecture, features, setup guides
├── migrations/        # Doctrine database migrations
├── public/
│   └── pdfs/          # Uploaded PDF storage directory
├── src/
│   ├── Command/       # Symfony console commands (indexing, translation monitoring)
│   ├── Contract/      # PHP interfaces (SearchEngineInterface, EmbeddingServiceInterface, etc.)
│   ├── Controller/    # HTTP controllers (Search, Pdf, Analytics, Translation, Home)
│   ├── DTO/           # Data Transfer Objects (SearchResult, PdfPageDocument)
│   ├── Entity/        # Doctrine ORM entities (SearchAnalytics, TranslationJob, PdfPageTranslation)
│   ├── Message/       # Symfony Messenger message classes (readonly)
│   ├── MessageHandler/# Async message handlers
│   ├── Repository/    # Doctrine repositories (SearchAnalyticsRepository, TranslationJobRepository)
│   ├── Search/        # Search strategy enums and query builders
│   ├── Service/       # Business logic services (Elasticsearch, Ollama, Translation, Analytics, PDF)
│   └── Shared/        # Shared utilities (SafeCallerTrait)
├── templates/         # Twig templates
├── tests/
│   ├── Unit/          # Unit tests (27+ test files)
│   ├── Integration/   # Integration tests (repository with real DB)
│   └── Functional/    # Functional/controller tests
├── .env               # Committed — safe defaults only
├── .env.test          # Test environment config
├── biome.json         # JavaScript/Vue linting (replaces ESLint/Prettier)
├── composer.json      # PHP dependencies + scripts
├── docker-compose.yml         # Base config (production defaults)
├── docker-compose.dev.yml     # Dev overrides (ports exposed, no auth, bind mounts)
├── docker-compose.prod.yml    # Prod overrides (resource limits, security hardened)
├── Makefile           # Canonical entry point for all operations
├── package.json       # Node dependencies
├── phpstan.neon       # PHPStan Level 8 config
├── phpunit.xml.dist   # Test config, 93% coverage target (85% CI minimum)
├── rector.php         # Rector rules (PHP 8.4, Symfony 7.4, Doctrine 3.x)
├── webpack.config.js  # Encore: 3 entries, code splitting
└── TODO.md            # Prioritized backlog
```

---

## Architecture Patterns

### Design Patterns in Use
- **Service-Oriented Architecture** with clear layer separation
- **Repository Pattern** — all database queries go through Repository classes
- **Interface/Contract Pattern** — all major services implement interfaces in `src/Contract/`
- **DTO Pattern** — `SearchResult`, `PdfPageDocument` for typed data transfer
- **Command Pattern** — Symfony Messenger for async processing (analytics logging, translation jobs)
- **Strategy Pattern** — `SearchStrategy` enum drives query builder selection

### Interfaces (Never bypass these)
| Interface | Implementation | Purpose |
|---|---|---|
| `SearchEngineInterface` | `ElasticsearchService` | Search + indexing |
| `PdfIndexerInterface` | `ElasticsearchService` | PDF indexing |
| `QueryBuilderInterface` | `SearchQueryBuilder`, `HybridSearchQueryBuilder` | Query DSL construction |
| `EmbeddingServiceInterface` | `OllamaEmbeddingService` | Vector embeddings |
| `VectorStoreInterface` | `ElasticsearchVectorStore` | Vector index management |
| `RankFusionServiceInterface` | `ReciprocalRankFusionService` | Result merging/ranking |
| `PdfProcessorInterface` | `PdfProcessor` | PDF text extraction + OCR |
| `TranslationServiceInterface` | `TranslationService` | Translation cache/lookup/store |
| `LanguageDetectorInterface` | `LanguageDetector` | Language detection |
| `ExportFormatterInterface` | `ExportFormatterService` | CSV/JSON analytics export |

Always inject the interface, not the concrete class.

### Async Processing
- **Symfony Messenger** + Doctrine transport (database-backed queue)
- Message classes are `readonly` (immutable DTOs)
- 3 Supervisor workers consume the queue in production
- Use `LogSearchAnalyticsMessage` and `TranslatePageMessage` as templates for new messages

---

## Coding Standards — Strict Requirements

### PHP
- `declare(strict_types=1)` required in every file
- PHP 8.4 minimum — use readonly properties, constructor promotion, match expressions, enums
- PSR-12 code style (enforced by `php-cs-fixer`)
- PHPStan Level 8 — zero errors outside the approved baseline
- Constructor injection only — never use `new` for injected services
- Return type declarations required on all methods
- Prefer early returns / guard clauses over nested conditionals
- Use `readonly` properties wherever possible
- Use `#[Attribute]` annotations (not YAML/XML config for entities and routes)

### JavaScript / Vue.js
- Biome for linting and formatting (replaces ESLint + Prettier)
- Vue 3 Composition API with `<script setup>`
- No `var` — use `const` / `let`
- Self-closing tags for components without slots

### General Rules
- No dead code — if something is unused, delete it entirely
- No backwards-compatibility shims unless strictly required
- No comments that restate what the code does — only explain non-obvious logic
- Do not add docblocks or annotations to code you did not change

---

## Testing

### Coverage Target
- **CI minimum:** 85% line coverage (fails the build below this)
- **Project target:** 93% line coverage

### Running Tests
```bash
# All tests (inside PHP container)
make test
# or
docker compose exec php php bin/phpunit

# With coverage report
docker compose exec php php bin/phpunit --coverage-html var/coverage

# Specific suite
docker compose exec php php bin/phpunit tests/Unit
```

### Test Structure
- `tests/Unit/` — mock all external dependencies (DB, Elasticsearch, Ollama)
- `tests/Integration/` — uses real database; requires running `database` service
- `tests/Functional/` — full Symfony kernel (HTTP tests via BrowserKit)

### What to Test
- Every new service class must have a Unit test
- Every new repository method must have an Integration test
- Every new controller action must have a Functional test
- Use Symfony's `KernelTestCase` for integration, `WebTestCase` for functional
- Do not test Symfony internals or Doctrine behavior — test your code's logic

### Test Fixtures
- Elasticsearch mock responses go in `tests/Fixtures/`
- Do not use real Elasticsearch in Unit tests — always mock `ElasticsearchService`
- When mocking services that implement interfaces, **always mock the interface** (`LanguageDetectorInterface`, `PdfProcessorInterface`, etc.) — never the concrete class

---

## SEO Architecture

### Meta Layer (`templates/base.html.twig`)
Every page inherits these blocks — override per template as needed:

| Twig block | Purpose | Default |
|---|---|---|
| `title` | `<title>` tag | "PDF Content Search — AI-Powered Document Search" |
| `meta_description` | `<meta name="description">` | Generic description |
| `meta_robots` | `<meta name="robots">` | `index, follow` |
| `canonical` | `<link rel="canonical">` | `{{ url('home') }}` |
| `og_title` / `og_description` / `og_type` | Open Graph | Inherits title/description |
| `structured_data` | JSON-LD `<script>` block | WebSite + SearchAction |

**Rules:**
- Always use `{{ url('route_name') }}` for canonical — never string concatenation (`schemeAndHttpHost ~ pathInfo` has trailing slash issues and exposes query strings)
- Non-public pages (`/analytics`, `/viewer`, `/api/*`) must set `{% block meta_robots %}noindex, nofollow{% endblock %}`
- `APP_ENV=dev` injects `X-Robots-Tag: noindex` via Symfony WebProfiler — Lighthouse SEO scores are only meaningful in `APP_ENV=prod`

### robots.txt + sitemap.xml
Served dynamically by `SitemapController` — **never use static files in `public/`** for these:
- Static files cannot generate absolute URLs; the `Sitemap:` directive requires an absolute URL
- A robots.txt with a relative `Sitemap:` is syntactically invalid and scores worse than no robots.txt
- `SitemapController` uses `$request->getSchemeAndHttpHost()` to build absolute URLs at runtime

### Indexability by Route
| Route | Indexable | Reason |
|---|---|---|
| `/` | ✅ `index, follow` | Main entry point |
| `/sitemap.xml` | ✅ technical | Served by `SitemapController` |
| `/robots.txt` | ✅ technical | Served by `SitemapController` |
| `/viewer` | ❌ `noindex` | Dynamic, user-specific content |
| `/analytics` | ❌ `noindex` | Internal dashboard |
| `/api/*` | ❌ `Disallow` | REST endpoints, not HTML pages |

---

## Accessibility Standards

### ARIA Patterns in Use
- **Search input** (`Bar.vue`): full combobox pattern — `role="combobox"`, `aria-expanded`, `aria-haspopup="listbox"`, `aria-controls`, `aria-activedescendant`
- **Suggestions list** (`Suggestions.vue`): `role="listbox"` with `role="option"` items and `id="suggestion-{index}"` for `aria-activedescendant` linking
- **Skip link** (`base.html.twig`): `href="#main-content"`, visually hidden with `-translate-y-full`, appears on focus via `focus:translate-y-0`
- **Page landmark**: `<main id="main-content">` wraps all page content in every template

### Decorative SVGs — Required Pattern
All decorative SVGs (icon-only, visual) **must** have:
```html
<svg aria-hidden="true" focusable="false" ...>
```
- `aria-hidden="true"` — removes from accessibility tree; the parent button/link label describes the action
- `focusable="false"` — required for IE11/Edge legacy (SVGs were focusable by default)
- **Never use `replace_all` to add these attributes** — it strips the closing `>` from the tag; always use individual targeted edits

### Tap Targets (WCAG 2.5.5)
All interactive elements must meet **48×48 CSS pixels minimum**:
```html
class="min-w-[48px] min-h-[48px] flex items-center justify-center"
```
Affected components: `Pagination.vue`, `Controls.vue`, `Bar.vue` (clear button), `ResultCard.vue` (View PDF link).

### `sr-only` vs `aria-hidden` — When to Use Each
| Technique | Effect | Use when |
|---|---|---|
| `class="sr-only"` | Visually hidden, read by screen reader | Adding semantic context without changing visual design (labels, descriptions) |
| `aria-hidden="true"` | Hidden from screen reader, visible on screen | Decorative elements already described by adjacent text or parent label |

---

## Search Architecture

### Flow
```
QueryParser → SearchStrategy (HYBRID|LEXICAL|PREFIX)
  → HybridSearchQueryBuilder
      → SearchQueryBuilder (BM25 lexical DSL)
      → OllamaEmbeddingService (768-dim vector)
      → ElasticsearchService (dual query)
      → ReciprocalRankFusionService (RRF merge: 1/(60+rank))
  → SearchResult DTO
  → AnalyticsCollector (async via Messenger)
```

### Elasticsearch Index: `pdf_pages`
| Field | Type | Notes |
|---|---|---|
| `pdf_filename` | keyword | Exact match |
| `page_number` | integer | 1-based |
| `text` | text (BM25) | `index_options: offsets` for highlighting |
| `text_embedding` | dense_vector 768d | `int8_hnsw`, cosine similarity |
| `language` | keyword | ISO code |
| `created_at` | date | |

### Performance Rules
- Bulk indexing: 500-page streaming buffer — do not change without benchmarking
- `int8_hnsw` quantization saves 4x RAM vs float32 — do not switch to float32
- Exclude `text_embedding` from `_source` (saves ~300KB/doc per hit)
- Safety cap: `ELASTICSEARCH_MAX_RESULTS=100` — never remove this limit

---

## Security — Non-Negotiable Rules

### Never Do These
- Never commit secrets to git (no `.env.local`, `.env.prod.local` — these are in `.gitignore`)
- Never disable Elasticsearch authentication in production
- Never expose database or Elasticsearch ports in production (only internal Docker network)
- Never store raw user IPs — mask last 80 bits of IPv6 before persisting
- Never skip Symfony Validator on user input
- Never bypass CSRF protection (never add `methods: ['GET']` on state-changing routes)

### Production Secrets
- `APP_SECRET`, `POSTGRES_PASSWORD`, `ELASTIC_PASSWORD` must never be in `.env`
- Use `.env.prod.local` (gitignored) or environment variables injected by the host
- Rotate secrets immediately if exposed

### Pending Security Work (High Priority)
See `TODO.md`. These must be completed before public exposure:
- API authentication (token-based or session)
- Rate-limiting on `/search`, `/translate`, `/analytics` endpoints
- HTTPS enforcement + HSTS headers
- GDPR data retention: auto-delete `SearchAnalytics` records older than 90 days

### Dependency Security
- `npm audit` — high/critical findings block CI
- `composer audit` — CVE or abandoned packages block CI
- Fix vulnerabilities by upgrading; do not suppress audit warnings without justification

---

## Database & Migrations

### Rules
- Every schema change requires a Doctrine migration: `docker compose exec php php bin/console make:migration`
- Always review auto-generated migration before committing
- Mark backfill migrations as `IrreversibleMigration` — they cannot be rolled back
- Never alter existing migration files — always create new ones
- Run migrations in CI before integration tests

### Running Migrations
```bash
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### Entities Location
`src/Entity/` — three entities: `SearchAnalytics`, `PdfPageTranslation`, `TranslationJob`

---

## Environment Variables

| Variable | Default | Notes |
|---|---|---|
| `APP_ENV` | `dev` | `dev` / `prod` / `test` |
| `APP_SECRET` | (dev value) | **Must be overridden in prod** |
| `DATABASE_URL` | PostgreSQL connection | Includes credentials |
| `ELASTICSEARCH_HOST` | `http://elasticsearch:9200` | In prod: `http://elastic:PASS@host:9200` |
| `ELASTICSEARCH_INDEX_PDFS` | `pdf_pages` | Main search index |
| `ELASTICSEARCH_MAX_RESULTS` | `100` | Safety cap — do not raise without load testing |
| `OLLAMA_HOST` | `http://ollama:11434` | AI model server |
| `OLLAMA_MODEL` | `qwen2.5:7b` | Translation model |
| `OLLAMA_EMBEDDING_MODEL` | `nomic-embed-text` | Embedding model (768d) |
| `MESSENGER_TRANSPORT_DSN` | `doctrine://default?auto_setup=0` | Async queue |

Secrets (`APP_SECRET`, passwords) go in `.env.local` (dev) or `.env.prod.local` (prod) — never in `.env`.

---

## Docker & Infrastructure

### Development Setup
```bash
make dev        # Build + start all services with hot-reload
make up         # Start without rebuilding
make shell      # Bash shell in PHP container
make logs       # Follow all logs
make status     # Health check all services
```

### Production Deployment
```bash
# Requires .env.prod and .env.prod.local with real secrets
make prod
```

### Services
| Service | Image | Notes |
|---|---|---|
| `php` | Custom (`.docker/dev/app/Dockerfile`) | PHP 8.4-FPM |
| `apache` | httpd:2.4.66-alpine | Reverse proxy to FPM |
| `database` | postgres:16-alpine | PostgreSQL |
| `elasticsearch` | elasticsearch:9.3.0 | Search + vectors |
| `ollama` | Custom (`.docker/ollama/Dockerfile`) | AI models — entrypoint auto-pulls models on start |

### Docker Composition Pattern
- `docker-compose.yml` — base (production defaults, no port exposure)
- `docker-compose.dev.yml` — dev overrides (ports, bind mounts, no auth)
- `docker-compose.prod.yml` — prod overrides (resource limits, security)

Never put development conveniences into the base `docker-compose.yml`.

---

## Makefile — Canonical Entry Point

Always use `make` for common tasks. Check `Makefile` before running raw docker/composer/npm commands.

| Command | Purpose |
|---|---|
| `make dev` | Build and start development environment |
| `make up` | Start services (no rebuild) |
| `make down` | Stop all services |
| `make test` | Run PHPUnit test suite |
| `make phpstan` | Run PHPStan Level 8 static analysis |
| `make rector` | Run Rector in check mode (no writes) |
| `make rector-fix` | Apply Rector refactoring |
| `make shell` | Open bash in PHP container |
| `make logs` | Tail all container logs |
| `make prod` | Start production stack |

---

## CI/CD Pipeline

### GitHub Actions Workflows
| Workflow | Trigger | Checks |
|---|---|---|
| `ci.yml` | push / PR | PHPUnit (85% coverage), PHPStan, PHP-CS-Fixer, Rector, Biome, Webpack build |
| `security-audit.yml` | push / PR / daily 2am UTC | npm audit, composer audit, dependency-review |
| `codeql.yml` | push / PR | SAST (PHP vulnerability scanning) |

### CI Requirements (All Must Pass)
1. PHPUnit — ≥85% line coverage
2. PHPStan — Level 8, zero new errors
3. PHP-CS-Fixer — zero style violations
4. Rector — zero unapplied changes
5. Biome — zero lint/format issues
6. Webpack build — must complete successfully
7. npm audit — no high/critical CVEs
8. composer audit — no CVEs, no abandoned packages

Do not merge pull requests with CI failures.

---

## Husky Pre-commit Hook

`.husky/pre-commit` runs automatically on every `git commit` when the dev containers are up.

### Design Rules
- **Graceful skip** — if the `php` container is not running, the hook exits 0 and warns; CI is the real quality gate
- **No coverage in pre-commit** — `XDEBUG_MODE=coverage` is intentionally absent; coverage instrumentation makes the suite 5–10x slower and belongs in CI only (`--coverage-clover coverage.xml`)
- **Mirrors `composer ci`** — checks run in the same order and use the same composer scripts for consistency

### Checks (in order)
| Step | Command | Fail action |
|---|---|---|
| PHP-CS-Fixer | `composer cs-check` | `Run: make cs-fix` |
| PHPStan | `composer phpstan` | Fix errors before committing |
| Rector | `composer rector-check` | `Run: make rector-fix` |
| PHPUnit | `composer test` | Fix failing tests |
| Biome | `npm run lint --silent` | `Run: npm run lint` |

### Why Keep Husky Separate from `composer ci`
- `composer ci` is PHP-only — cannot include Biome (Node.js)
- Husky provides per-check actionable error messages and graceful skip logic
- `composer ci` is the local convenience script; Husky is the automated gate

---

## Code Quality Tools

### PHP Static Analysis
```bash
# Run all checks in sequence (local CI equivalent)
docker compose exec php composer ci

# PHPStan
docker compose exec php composer phpstan
# or: make phpstan

# PHP-CS-Fixer (check only)
docker compose exec php composer cs-check

# PHP-CS-Fixer (fix)
docker compose exec php composer cs-fix

# Rector (check — see what would change, no writes)
docker compose exec php composer rector-check
# or: make rector

# Rector (apply)
docker compose exec php composer rector
# or: make rector-fix

# Run tests
docker compose exec php composer test
# or: make test
```

### Frontend Linting
```bash
npm run lint        # Biome lint check
npm run format      # Biome format check
```

### PHPStan Baseline
`phpstan-baseline.neon` contains approved existing errors. Do not add new entries to the baseline without team review. Zero tolerance for regressions.

---

## Ollama Models

Models are **automatically downloaded on container startup** via `.docker/ollama/entrypoint.sh`.
No manual step is required on first deploy or after `make clean`.

- On start: `ollama serve` launches, then the entrypoint pulls missing models
- Idempotent: skips download if models already exist in the `ollama_data` volume
- Healthcheck verifies `nomic-embed-text` is present (`start_period: 300s` for first pull)

Models pulled:
| Model | Size | Purpose |
|---|---|---|
| `qwen2.5:7b` | ~4.7 GB | Translation |
| `nomic-embed-text` | ~274 MB | Embeddings (768d) |

---

## PDF Indexing

```bash
# Create Elasticsearch index (run once)
docker compose exec php php bin/console app:create-pdf-index

# Index all PDFs from public/pdfs/
docker compose exec php php bin/console app:index-pdfs
```

- PDFs go in `public/pdfs/` directory
- Text extraction: `pdftotext` (poppler), OCR fallback via `ocrmypdf`
- Shell commands run via `Symfony\Component\Process\Process` (args as array — no shell interpolation, 300s timeout on OCR)
- Indexing: 500-page streaming buffer to bound memory
- Embeddings generated by Ollama `nomic-embed-text`
- Dates stored as ISO 8601 (`DateTimeInterface::ATOM`) in Elasticsearch

---

## Translation Jobs

```bash
# Monitor translation job queue
docker compose exec php php bin/console app:translation:monitor --watch
# or:
./bin/monitor-jobs.sh --watch

# Worker logs
./bin/worker-logs.sh -f
```

- Translation model: Ollama `qwen2.5:7b` (4.7GB, 300s timeout per page)
- Cache TTL: 7 days (no re-translation of same page+language)
- Deduplication TTL: 5 minutes (prevents duplicate queue entries)
- Job statuses: `pending` → `processing` → `completed` / `failed`

---

## Analytics System

### Data Collected
- `query` — search string
- `results_count` — matches found
- `displayed_results_count` — min(total, pageSize=10)
- `click_position` — which result was clicked (1-based, nullable)
- `user_ip` — IPv6 with last 80 bits masked (GDPR)
- `search_strategy` — HYBRID / LEXICAL / PREFIX
- `created_at` — timestamp

### Async Logging Flow
`AnalyticsCollector` → `LogSearchAnalyticsMessage` → Messenger queue → `LogSearchAnalyticsHandler` → DB

### GDPR Requirement
Analytics must be auto-deleted after 90 days. This is not yet implemented (see TODO.md).

---

## Claude Code Hooks

Hooks in `.claude/hooks/` run automatically on every Claude Code session. Do not work around them — they enforce project standards.

### Active Hooks

| Hook | Event | Trigger | Purpose |
|---|---|---|---|
| `protect-secret-files.sh` | `PreToolUse` Read\|Grep | Any `.env*` file that is gitignored | Blocks reading files that may contain real secrets — ask the user for the specific values needed instead |
| `auto-format.sh` | `PostToolUse` Edit\|Write | Any `.php`, `.vue`, `.js`, `.ts` file | Runs `php-cs-fixer` (via Docker) or `biome format` automatically |
| `validate-service-interface.sh` | `PostToolUse` Write | New file in `src/Service/` | Warns if a concrete class does not `implement` a contract from `src/Contract/` |
| `validate-strict-types.sh` | `PostToolUse` Write | Any new `.php` file | Warns if `declare(strict_types=1)` is missing |
| `validate-test-exists.sh` | `PostToolUse` Write | New file in `src/Service/` | Warns if no corresponding `tests/Unit/Service/*Test.php` exists |

### Hook Output

- Hooks that output to **stdout** inject feedback directly into Claude's context — Claude will self-correct without user intervention.
- Hooks that exit with code **2** block the operation entirely (`protect-secret-files.sh`).
- All validation hooks exit **0** — they warn but never block.

### Secret File Protection

Never attempt to read `.env.local`, `.env.prod`, `.env.prod.local`, or any other gitignored env file. The hook will block the read and instruct you to ask the user for the specific variable values needed.

---

## Slash Commands

Custom slash commands in `.claude/commands/`. Invoke them by typing `/command-name` in Claude Code.

| Command | File | Purpose |
|---|---|---|
| `/new-service` | `new-service.md` | Creates interface + service + DI binding + unit test in one guided flow |
| `/quality-check` | `quality-check.md` | Runs PHPStan → PHP-CS-Fixer → Rector → Biome in sequence; stops on first failure |
| `/security-audit` | `security-audit.md` | Runs `composer audit` + `npm audit`; reports vulnerabilities by severity |

### Usage Notes
- `/new-service` accepts arguments: `/new-service ReportExporter - exports analytics as PDF`
- `/quality-check` and `/security-audit` require the Docker stack to be running (`make dev`)
- Both PHP tools run inside the `php` container; Biome also runs inside the container via `npm run lint`

---

## Common Pitfalls to Avoid

1. **Adding new services without interfaces** — all services must implement a contract in `src/Contract/`
2. **Synchronous Ollama calls in HTTP requests** — always dispatch via Messenger for long operations
3. **Raising `ELASTICSEARCH_MAX_RESULTS` without load testing** — this protects the cluster
4. **Calling `ElasticsearchService` directly in tests** — mock via `SearchEngineInterface`
5. **Skipping migrations** — never alter the schema without a Doctrine migration
6. **Hardcoding credentials** — use environment variable injection always
7. **Bypassing IP anonymization** — store masked IPs only (last 80 bits zeroed)
8. **Changing `int8_hnsw` to `float32` embeddings** — 4x RAM increase; benchmark first
9. **Modifying existing Dockerfile `FROM` base images** — pin exact versions, test thoroughly
10. **Adding frontend tests with Jest** — this project uses Biome only; tests not yet implemented (see TODO.md — add Vitest when ready)
11. **Using `exec()`/`shell_exec()` for system commands** — always use `Symfony\Component\Process\Process` with args as array; prevents shell injection and allows timeout control
12. **Mocking concrete classes in tests** — mock the interface (`LanguageDetectorInterface`, `PdfProcessorInterface`, `TranslationServiceInterface`); tight coupling to concrete classes hides architectural problems
13. **Storing dates as `Y-m-d H:i:s` strings in Elasticsearch** — use `DateTimeInterface::ATOM` (ISO 8601) to enable date math, `date_histogram`, and proper timezone handling
14. **Using `replace_all` on SVG attribute strings ending with `>`** — strips the closing `>` from the tag, corrupting HTML silently and breaking the Webpack build; always use individual targeted edits for SVG attributes
15. **Serving `robots.txt`/`sitemap.xml` as static files in `public/`** — static files cannot generate absolute URLs; use `SitemapController` with `$request->getSchemeAndHttpHost()`
16. **Running Lighthouse SEO in `APP_ENV=dev`** — Symfony WebProfiler injects `X-Robots-Tag: noindex` on all responses; SEO scores are only accurate in `APP_ENV=prod`

---

## Key Files Reference

| File | Purpose |
|---|---|
| `src/Service/ElasticsearchService.php` | Core search + indexing logic |
| `src/Service/HybridSearchQueryBuilder.php` | Hybrid search (BM25 + kNN + RRF) |
| `src/Service/ReciprocalRankFusionService.php` | RRF ranking algorithm |
| `src/Service/OllamaEmbeddingService.php` | Vector embedding generation |
| `src/Service/OllamaService.php` | Translation via Ollama |
| `src/Service/TranslationService.php` | Translation cache → DB → AI chain; implements `TranslationServiceInterface` |
| `src/Service/TranslationOrchestrator.php` | Translation workflow coordination; injects `PdfProcessorInterface` + `TranslationServiceInterface` |
| `src/Service/AnalyticsCollector.php` | Metrics recording |
| `src/Service/ExportFormatterService.php` | CSV/JSON export formatting; implements `ExportFormatterInterface` |
| `src/Service/PdfProcessor.php` | PDF text extraction + OCR via `Symfony\Process`; implements `PdfProcessorInterface` |
| `src/Service/LanguageDetector.php` | Language detection; `final`, implements `LanguageDetectorInterface` |
| `src/Controller/SearchController.php` | Search HTTP API |
| `src/Controller/AnalyticsController.php` | Analytics REST API |
| `src/Controller/SitemapController.php` | Dynamic `robots.txt` + `sitemap.xml` with absolute URLs |
| `src/Controller/TranslationController.php` | Translation HTTP API |
| `src/Search/SearchStrategy.php` | Search strategy enum (HYBRID/LEXICAL/PREFIX) |
| `src/DTO/SearchResult.php` | Typed search result DTO |
| `src/DTO/PdfPageDocument.php` | Typed PDF page DTO |
| `src/Contract/` | All 10 interfaces — single source of truth for injectable contracts |
| `config/services.yaml` | DI container config (interface bindings) |
| `Makefile` | All common operations |
| `TODO.md` | Prioritized backlog |
| `docs/refactor-contract-first-process-hardening/` | Refactoring session docs (findings, plan, progress log) |
| `docs/tasks/seo-accessibility-lighthouse/` | SEO + a11y audit session docs (findings, plan, progress log) |
| `templates/base.html.twig` | Master template — SEO meta, canonical, OG, JSON-LD, skip link, `<main>` |

---

## Backlog Priorities (from TODO.md)

### High Priority — Address Before Public Release
- [ ] API authentication (token-based)
- [ ] Rate-limiting on search, translate, analytics endpoints
- [ ] HTTPS enforcement in production
- [ ] GDPR data retention (auto-delete analytics after 90 days)

### Medium Priority
- [ ] Daily analytics aggregation (pre-compute KPIs to reduce query load)
- [ ] Embedding generation optimization (GPU acceleration or faster model)
- [ ] Filter analytics dashboard by search strategy

### Low Priority
- [ ] JavaScript/Vue unit tests (Vitest)
- [ ] Real-time analytics dashboard (WebSocket)
- [ ] Scheduled email reports
- [ ] ML-powered query suggestions
