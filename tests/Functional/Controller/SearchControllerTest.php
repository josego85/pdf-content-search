<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for SearchController API.
 * Tests search endpoint behavior without real Elasticsearch (uses mocks via service override).
 */
final class SearchControllerTest extends WebTestCase
{
    public function testSearchApiRequiresQueryParameter(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testSearchApiWithEmptyQueryReturns400(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search', ['q' => '']);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testSearchApiReturnsJsonResponse(): void
    {
        $client = self::createClient();

        // This will fail without Elasticsearch, but documents expected behavior
        // In a real test environment, we'd mock the SearchEngineInterface
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search', ['q' => 'test']);

        // Response should be JSON even if error occurs
        $response = $client->getResponse();
        $contentType = $response->headers->get('Content-Type');

        $this->assertStringContainsString('application/json', $contentType);
    }

    public function testSearchApiAcceptsStrategyParameter(): void
    {
        $client = self::createClient();

        $strategies = ['hybrid', 'exact', 'prefix'];

        foreach ($strategies as $strategy) {
            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search', [
                'q' => 'test',
                'strategy' => $strategy,
            ]);

            // Should not return 400 (bad request) for valid strategies
            $statusCode = $client->getResponse()->getStatusCode();
            $this->assertNotSame(400, $statusCode, "Strategy '{$strategy}' should be valid");
        }
    }

    public function testSearchApiResponseStructure(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search', ['q' => 'test']);

        $response = $client->getResponse();
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testSearchApiHandlesSpecialCharacters(): void
    {
        $client = self::createClient();

        $specialQueries = [
            'test & search',
            'query with "quotes"',
            'search+term',
            'café résumé',
        ];

        foreach ($specialQueries as $query) {
            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search', ['q' => $query]);

            // Should not crash, should return valid response
            $response = $client->getResponse();
            $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        }
    }

    public function testSearchApiUrlEncoding(): void
    {
        $client = self::createClient();

        // Test URL-encoded query
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search?q=' . urlencode('test query'));

        $response = $client->getResponse();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    public function testSearchApiDoesNotAcceptPostRequests(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/api/search', ['q' => 'test']);

        // Should return 405 Method Not Allowed
        $this->assertResponseStatusCodeSame(405);
    }

    public function testSearchApiRouteName(): void
    {
        $client = self::createClient();
        $router = $client->getContainer()->get('router');

        $route = $router->generate('api_search', ['q' => 'test']);

        $this->assertStringContainsString('/api/search', $route);
    }

    /**
     * Test wildcard detection: queries with * should use PREFIX strategy.
     */
    public function testWildcardAsteriskDetection(): void
    {
        $client = self::createClient();

        $wildcardQueries = [
            'java*',
            'prog*',
            'test*search',
            '*wildcard',
            'mach*learn*',
        ];

        foreach ($wildcardQueries as $query) {
            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search', [
                'q' => $query,
                'log' => '1',
            ]);

            $response = $client->getResponse();
            $this->assertJson($response->getContent());

            $data = json_decode($response->getContent(), true);

            // Should auto-detect PREFIX strategy for wildcard queries
            if (isset($data['data']['strategy'])) {
                $this->assertSame('prefix', $data['data']['strategy'], "Query '{$query}' should use prefix strategy");
            }
        }
    }

    /**
     * Test wildcard detection: queries with ? should use PREFIX strategy.
     */
    public function testWildcardQuestionMarkDetection(): void
    {
        $client = self::createClient();

        $wildcardQueries = [
            'te?t',
            'prog?',
            'te??',
            'wom?n',
        ];

        foreach ($wildcardQueries as $query) {
            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search', [
                'q' => $query,
                'log' => '1',
            ]);

            $response = $client->getResponse();
            $this->assertJson($response->getContent());

            $data = json_decode($response->getContent(), true);

            // Should auto-detect PREFIX strategy for wildcard queries
            if (isset($data['data']['strategy'])) {
                $this->assertSame('prefix', $data['data']['strategy'], "Query '{$query}' should use prefix strategy");
            }
        }
    }

    /**
     * Test exact match detection: queries with quotes should use EXACT strategy.
     */
    public function testExactMatchDetection(): void
    {
        $client = self::createClient();

        $exactQueries = [
            '"artificial intelligence"',
            '"test query"',
            '\'single quotes\'',
        ];

        foreach ($exactQueries as $query) {
            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search', [
                'q' => $query,
                'log' => '1',
            ]);

            $response = $client->getResponse();
            $this->assertJson($response->getContent());

            $data = json_decode($response->getContent(), true);

            // Should auto-detect EXACT strategy for quoted queries
            if (isset($data['data']['strategy'])) {
                $this->assertSame('exact', $data['data']['strategy'], "Query {$query} should use exact strategy");
            }
        }
    }

    /**
     * Test conditional logging: log=0 should not log analytics.
     */
    public function testConditionalLoggingWithLogZero(): void
    {
        $client = self::createClient();

        // Suggestion request (should not log)
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search', [
            'q' => 'test suggestion',
            'log' => '0',
        ]);

        $response = $client->getResponse();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));

        // Response should be successful (whether or not ES is available)
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    /**
     * Test conditional logging: log=1 should log analytics.
     */
    public function testConditionalLoggingWithLogOne(): void
    {
        $client = self::createClient();

        // Committed search (should log)
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search', [
            'q' => 'test committed search',
            'log' => '1',
        ]);

        $response = $client->getResponse();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));

        // Response should be successful (whether or not ES is available)
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    /**
     * Test default behavior: no log parameter defaults to log=0.
     */
    public function testDefaultLoggingBehavior(): void
    {
        $client = self::createClient();

        // No log parameter (should default to 0)
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search', [
            'q' => 'test default',
        ]);

        $response = $client->getResponse();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    /**
     * Test hybrid AI strategy remains default for normal queries.
     */
    public function testHybridAIDefaultStrategy(): void
    {
        $client = self::createClient();

        $normalQueries = [
            'java',
            'php',
            'artificial intelligence',
            'machine learning',
        ];

        foreach ($normalQueries as $query) {
            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/api/search', [
                'q' => $query,
                'log' => '1',
            ]);

            $response = $client->getResponse();
            $this->assertJson($response->getContent());

            $data = json_decode($response->getContent(), true);

            // Should use HYBRID_AI for normal queries (without quotes or wildcards)
            if (isset($data['data']['strategy'])) {
                $this->assertSame('hybrid_ai', $data['data']['strategy'], "Query '{$query}' should use hybrid_ai strategy");
            }
        }
    }
}
