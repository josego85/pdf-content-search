# Testing Guide

Comprehensive testing suite for PDF Content Search application using PHPUnit.

## Quick Start

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suites
./vendor/bin/phpunit --testsuite=Unit
./vendor/bin/phpunit --testsuite=Integration
./vendor/bin/phpunit --testsuite=Functional

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/

# Run with testdox (readable output)
./vendor/bin/phpunit --testdox
```

## Quick Start with Docker

```bash
# Run tests inside Docker container
docker compose exec php vendor/bin/phpunit

# Run with coverage (PCOV enabled in dev environment)
docker compose exec php vendor/bin/phpunit --coverage-html coverage/

# Or use the helper script
./docker/dev/scripts/coverage.sh

# Open coverage report in browser
open coverage/index.html

# Generate XML coverage for CI
./docker/dev/scripts/coverage.sh --xml
```

**Note:** PCOV is pre-installed in the dev Docker image for fast code coverage reports. It's significantly faster than Xdebug and has zero runtime overhead.

## Test Structure

```
tests/
├── Unit/                  # Isolated unit tests (no external dependencies)
│   ├── Search/           # Query parsing and building logic
│   ├── Service/          # Service layer tests
│   └── Shared/           # Shared traits and utilities
├── Integration/          # Tests with real dependencies (Elasticsearch, DB)
│   ├── Command/          # Console command tests
│   └── Service/          # Service integration tests
├── Functional/           # HTTP endpoint tests
│   └── Controller/       # Controller action tests
└── Fixtures/             # Test data factories and helpers
    ├── Factory/          # Data factories (SearchResult, PdfDocument)
    └── TestPdfs/         # Sample PDF files for testing
```

## Test Coverage Goals

| Component | Target Coverage | Rationale |
|-----------|----------------|-----------|
| Business Logic (Search, Query) | **100%** | Critical functionality |
| Services | **90%+** | Core application layer |
| Controllers | **85%+** | API endpoints |
| Commands | **80%+** | Batch processes |
| Overall Project | **85%+** | Professional standard |

## Running Specific Tests

```bash
# Single test file
./vendor/bin/phpunit tests/Unit/Search/QueryParserTest.php

# Specific test method
./vendor/bin/phpunit --filter testParseSimpleQueries

# Test group (if defined)
./vendor/bin/phpunit --group elasticsearch
```

## Writing Tests

### Unit Test Example

```php
final class QueryParserTest extends TestCase
{
    private QueryParser $parser;

    protected function setUp(): void
    {
        $this->parser = new QueryParser();
    }

    /**
     * @dataProvider queryProvider
     */
    public function testParseQueryWithOperators(string $input, array $expected): void
    {
        // Arrange - prepare test data
        $query = $input;

        // Act - execute the code under test
        $result = $this->parser->parse($query);

        // Assert - verify the outcome
        $this->assertSame($expected, $result['required']);
    }

    public static function queryProvider(): array
    {
        return [
            'required terms' => ['+must +have', ['must', 'have']],
            'excluded terms' => ['-not -this', ['not', 'this']],
        ];
    }
}
```

### Best Practices

1. **AAA Pattern**: Arrange, Act, Assert
2. **One Assertion Per Concept**: Focus each test on a single behavior
3. **Descriptive Names**: `testSearchReturnsResultsWhenQueryMatches()`
4. **Use Constants**: Avoid magic numbers/strings
5. **Data Providers**: Test multiple scenarios efficiently
6. **No Code Duplication**: Use `setUp()`, helper methods, and traits

## Test Environment

### Configuration

- **Environment**: `APP_ENV=test` (`.env.test`)
- **Database**: SQLite in-memory (future use)
- **Elasticsearch**: Mock for unit tests, Docker for integration tests

### Environment Variables

```bash
# .env.test
APP_ENV=test
DATABASE_URL="sqlite:///:memory:"
ELASTICSEARCH_HOST=http://elasticsearch-test:9200
ELASTICSEARCH_INDEX_PDFS=pdf_pages_test
```

## Continuous Integration

Tests run automatically on:
- Every push to any branch
- Every pull request
- Daily scheduled runs (main branch)

See `.github/workflows/tests.yml` for CI configuration.

## Integration Tests with Elasticsearch

Integration tests requiring Elasticsearch are marked with `@group elasticsearch`:

```bash
# Skip Elasticsearch tests
./vendor/bin/phpunit --exclude-group elasticsearch

# Run only Elasticsearch tests
./vendor/bin/phpunit --group elasticsearch
```

**Docker Setup for Integration Tests**:

```bash
# Start test Elasticsearch instance
docker-compose -f docker-compose.test.yml up -d elasticsearch-test

# Run integration tests
./vendor/bin/phpunit --testsuite=Integration

# Stop test services
docker-compose -f docker-compose.test.yml down
```

## Code Coverage

Generate detailed coverage reports:

```bash
# HTML report (open coverage/index.html in browser)
./vendor/bin/phpunit --coverage-html coverage/

# Terminal summary
./vendor/bin/phpunit --coverage-text

# XML (for CI/CD tools)
./vendor/bin/phpunit --coverage-clover coverage.xml
```

## Debugging Tests

```bash
# Verbose output
./vendor/bin/phpunit -v

# Stop on first failure
./vendor/bin/phpunit --stop-on-failure

# Show detailed failure information
./vendor/bin/phpunit --testdox-text results.txt
```

## Mocking Strategy

- **PHPUnit Built-in Mocks**: For interfaces and simple classes
- **Test Doubles**: Manual mocks for complex Elasticsearch responses
- **Traits**: `ElasticsearchTestTrait` for consistent Elasticsearch mocking

Example:

```php
use App\Tests\Fixtures\ElasticsearchTestTrait;

final class ServiceTest extends TestCase
{
    use ElasticsearchTestTrait;

    public function testSearch(): void
    {
        $client = $this->createElasticsearchClientMock();
        $response = $this->createSearchResponse($hits);

        $client->expects($this->once())
            ->method('search')
            ->willReturn($response);

        // ... test logic
    }
}
```

## Common Issues

### Issue: Elasticsearch Connection Errors

**Solution**: Use mocks for unit tests, skip integration tests without Docker

```bash
./vendor/bin/phpunit --exclude-group elasticsearch
```

### Issue: Functional Tests Fail (Missing Assets)

**Solution**: Build frontend assets or skip functional tests in CI

```bash
npm run build  # or: yarn build
```

### Issue: PDF Processing Tests

**Solution**: `pdftotext` not required for unit tests (mocked). Install `poppler-utils` for integration tests.

## Factories and Fixtures

Reusable test data creation:

```php
use App\Tests\Fixtures\Factory\SearchResultFactory;

$result = SearchResultFactory::create()
    ->withTitle('test.pdf')
    ->withPage(5)
    ->withHighlight(['<mark>search</mark> term'])
    ->build();

$response = SearchResultFactory::create()
    ->buildEsResponse($hits);
```

## Performance

- **Unit tests**: < 1 second
- **Integration tests**: < 10 seconds
- **Functional tests**: < 5 seconds
- **Full suite**: < 15 seconds

Slow tests indicate architectural issues requiring refactoring.

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Symfony Testing Best Practices](https://symfony.com/doc/current/testing.html)
- [Martin Fowler: Test Pyramid](https://martinfowler.com/articles/practical-test-pyramid.html)

---

**Maintained by**: Development Team
**Last Updated**: 2025-11-09
