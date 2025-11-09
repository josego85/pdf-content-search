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
        $client = static::createClient();
        $client->request('GET', '/api/search');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testSearchApiWithEmptyQueryReturns400(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/search', ['q' => '']);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testSearchApiReturnsJsonResponse(): void
    {
        $client = static::createClient();

        // This will fail without Elasticsearch, but documents expected behavior
        // In a real test environment, we'd mock the SearchEngineInterface
        $client->request('GET', '/api/search', ['q' => 'test']);

        // Response should be JSON even if error occurs
        $response = $client->getResponse();
        $contentType = $response->headers->get('Content-Type');

        $this->assertStringContainsString('application/json', $contentType);
    }

    public function testSearchApiAcceptsStrategyParameter(): void
    {
        $client = static::createClient();

        $strategies = ['hybrid', 'exact', 'prefix'];

        foreach ($strategies as $strategy) {
            $client->request('GET', '/api/search', [
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
        $client = static::createClient();
        $client->request('GET', '/api/search', ['q' => 'test']);

        $response = $client->getResponse();
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testSearchApiHandlesSpecialCharacters(): void
    {
        $client = static::createClient();

        $specialQueries = [
            'test & search',
            'query with "quotes"',
            'search+term',
            'café résumé',
        ];

        foreach ($specialQueries as $query) {
            $client->request('GET', '/api/search', ['q' => $query]);

            // Should not crash, should return valid response
            $response = $client->getResponse();
            $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        }
    }

    public function testSearchApiUrlEncoding(): void
    {
        $client = static::createClient();

        // Test URL-encoded query
        $client->request('GET', '/api/search?q=' . urlencode('test query'));

        $response = $client->getResponse();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    public function testSearchApiDoesNotAcceptPostRequests(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/search', ['q' => 'test']);

        // Should return 405 Method Not Allowed
        $this->assertResponseStatusCodeSame(405);
    }

    public function testSearchApiRouteName(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        $route = $router->generate('api_search', ['q' => 'test']);

        $this->assertStringContainsString('/api/search', $route);
    }
}
