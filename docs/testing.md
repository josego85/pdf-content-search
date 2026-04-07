# Testing Guide

Full test suite for PDF Content Search: PHPUnit (PHP) + Vitest (JavaScript/Vue).

---

## PHP Tests (PHPUnit)

### Quick Start

```bash
# Run all PHP tests (inside Docker)
make test
# or:
docker compose exec php php bin/phpunit

# With HTML coverage report
docker compose exec php php bin/phpunit --coverage-html var/coverage

# Specific suite
docker compose exec php php bin/phpunit tests/Unit
docker compose exec php php bin/phpunit tests/Integration
docker compose exec php php bin/phpunit tests/Functional

# Verbose / stop on failure
docker compose exec php php bin/phpunit -v
docker compose exec php php bin/phpunit --stop-on-failure
```

### Test Structure

```
tests/
├── Unit/                  # Isolated — all external deps mocked
│   ├── Search/            # Query builder + strategy logic
│   ├── Service/           # Service layer (Elasticsearch, Ollama, Analytics…)
│   └── Shared/            # SafeCallerTrait and utilities
├── Integration/           # Real PostgreSQL — requires running database service
│   └── Repository/        # SearchAnalyticsRepository, TranslationJobRepository
├── Functional/            # Full Symfony kernel via BrowserKit (HTTP)
│   └── Controller/        # SearchController, AnalyticsController, etc.
└── Fixtures/              # Elasticsearch mock responses
```

### Coverage Targets

| Suite | CI Minimum | Project Target |
|---|---|---|
| Overall | **85%** | **93%** |
| Services | 90%+ | 95%+ |
| Controllers | 85%+ | 90%+ |

### Writing PHP Tests

**Rules:**
- Always mock the **interface**, never the concrete class (`SearchEngineInterface`, not `ElasticsearchService`)
- Unit tests: no real DB, no real Elasticsearch, no real Ollama — mock everything external
- Integration tests: use `KernelTestCase` + real `database` service
- Functional tests: use `WebTestCase`; Elasticsearch is always mocked

```php
final class OllamaEmbeddingServiceTest extends TestCase
{
    public function testGenerateEmbedding(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        // ... arrange, act, assert
    }
}
```

### Mocking Strategy

- Elasticsearch → mock `SearchEngineInterface` / `PdfIndexerInterface`
- Ollama → mock `EmbeddingServiceInterface` / `TranslationServiceInterface`
- PDF processing → mock `PdfProcessorInterface`
- Language detection → mock `LanguageDetectorInterface`

Never use `new ConcreteService()` in tests — inject mocked interfaces.

---

## JavaScript / Vue Tests (Vitest)

### Quick Start

```bash
# Run all JS/Vue tests (must run inside Docker)
docker compose exec php npm run test

# With coverage report (v8, HTML + terminal)
docker compose exec php npm run test:coverage

# Watch mode
docker compose exec php npm run test:watch
```

> **Why inside Docker?** `node_modules` is installed inside the Alpine container. The host lacks the native `@rolldown/binding-linux-x64-gnu` binding — running on the host throws `MODULE_NOT_FOUND`.

### Test Structure

```
tests/Javascript/
├── components/
│   ├── analytics/         # ClickPositionChart, ExportButtons, KPICard, StrategyDistribution, TopQueriesChart, TrendsChart
│   └── search/
│       ├── states/        # Empty, Error, Initial, Loading
│       ├── Bar, Controls, Hero, Pagination, ResultCard, Results, Search, Suggestions
├── constants/             # api, languages, pagination
├── services/              # TranslationApiService
└── setup.js               # Global setup (apexchart stub)
```

**172 tests — 22 test files**

### Coverage Thresholds (enforced in CI)

| Metric | Threshold | Current |
|---|---|---|
| Statements | 80% | ~89% |
| Branches | 80% | ~87% |
| Functions | 80% | ~81% |
| Lines | 80% | ~89% |

### Configuration

`vitest.config.js` key settings:
- **Environment:** `happy-dom` (jsdom@29 incompatible with Node 24 due to ESM top-level await)
- **Alias:** `@` → `assets/`
- **Setup file:** `tests/Javascript/setup.js` — stubs `apexchart` globally
- **Navigation:** `disableMainFrameNavigation: true` — prevents ECONNREFUSED from `<a target="_blank">` clicks

### Writing Vue Tests

```js
import { mount } from "@vue/test-utils"
import { beforeEach, afterEach, describe, expect, it, vi } from "vitest"
import MyComponent from "@/components/search/MyComponent.vue"

describe("MyComponent", () => {
    beforeEach(() => {
        globalThis.fetch = vi.fn().mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue({}) })
        vi.spyOn(console, "error").mockImplementation(() => {}) // silence expected errors
    })

    afterEach(() => {
        vi.restoreAllMocks()
    })

    it("renders correctly", () => {
        const wrapper = mount(MyComponent, { props: { value: "test" } })
        expect(wrapper.exists()).toBe(true)
    })
})
```

**Key patterns:**
- Stub `apexchart` globally in `setup.js` (not per-test) — globally registered tag, `vi.mock` alone is insufficient
- Mock `console.error` for error-path tests to suppress expected catch-block output
- Mock `window.open` when testing `<a target="_blank">` clicks
- **Never name a Vue import `Error`** — shadows the global `Error` constructor in Vitest SSR mode

---

## Continuous Integration

Both suites run in CI on every push and PR:

```yaml
# ci.yml jobs
php-tests:          PHPUnit — ≥85% line coverage
frontend-tests:     Vitest  — ≥80% all coverage metrics
```

See `.github/workflows/ci.yml` for full pipeline.

---

## Pre-commit Hook

`.husky/pre-commit` runs all checks when the `php` container is up:

| Step | Command |
|---|---|
| PHP-CS-Fixer | `composer cs-check` |
| PHPStan L8 | `composer phpstan` |
| Rector | `composer rector-check` |
| PHPUnit | `composer test` |
| Biome | `npm run lint` (inside Docker) |
| Vitest | `npm run test` (inside Docker) |

If the containers are not running, the hook exits 0 and warns — CI is the real quality gate.

---

## Common Issues

### Elasticsearch connection errors in PHP tests
All PHP tests mock Elasticsearch — no real connection is needed. If you see connection errors, check that you're not accidentally running unit tests without mocks.

### `MODULE_NOT_FOUND @rolldown/binding-linux-x64-gnu`
Run `npm run test` inside Docker, not on the host:
```bash
docker compose exec php npm run test
```

### ApexChart not found in Vue tests
The global stub is in `tests/Javascript/setup.js`. If you add a new chart component, ensure the setup file is loaded (it is, via `vitest.config.js` `setupFiles`).

### ECONNREFUSED localhost:3000 in Vitest output
Covered by `disableMainFrameNavigation: true` in `vitest.config.js`. If the noise reappears, verify the `environmentOptions.happyDOM.settings.navigation` block is present.

---

**Last Updated:** 2026-04-07
