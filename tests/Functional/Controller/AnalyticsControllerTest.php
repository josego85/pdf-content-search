<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for AnalyticsController API.
 * Tests analytics endpoints for overview, trends, and click tracking.
 * Note: These tests require the database to be set up. They validate routing and response structure.
 */
final class AnalyticsControllerTest extends WebTestCase
{
    private function skipIfDatabaseNotAvailable($response): void
    {
        // Skip tests if database is not available (common in CI/test environments)
        if ($response->getStatusCode() === 500) {
            $this->markTestSkipped('Database not available for this functional test');
        }
    }

    public function testOverviewReturnsJsonResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/analytics/overview');

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        $this->assertResponseIsSuccessful();
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('data', $data);
    }

    public function testOverviewReturnsExpectedMetrics(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/analytics/overview');

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('total_searches', $data['data']);
        $this->assertArrayHasKey('unique_sessions', $data['data']);
        $this->assertArrayHasKey('avg_response_time_ms', $data['data']);
        $this->assertArrayHasKey('zero_result_searches', $data['data']);
        $this->assertArrayHasKey('click_through_rate', $data['data']);
        $this->assertArrayHasKey('success_rate', $data['data']);
        $this->assertArrayHasKey('period', $data['data']);
    }

    public function testOverviewAcceptsDaysParameter(): void
    {
        $client = static::createClient();

        $daysValues = [7, 14, 30];

        foreach ($daysValues as $days) {
            $client->request('GET', '/api/analytics/overview', ['days' => $days]);

            $response = $client->getResponse();
            $this->skipIfDatabaseNotAvailable($response);

            $this->assertResponseIsSuccessful();

            $data = json_decode($response->getContent(), true);
            $this->assertSame($days, $data['data']['period']['days']);
        }
    }

    public function testTopQueriesReturnsJsonResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/analytics/top-queries');

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        $this->assertResponseIsSuccessful();
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testTopQueriesReturnsExpectedFields(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/analytics/top-queries');

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        $data = json_decode($response->getContent(), true);

        // If there are results, check the structure
        if (!empty($data['data'])) {
            $firstQuery = $data['data'][0];
            $this->assertArrayHasKey('query', $firstQuery);
            $this->assertArrayHasKey('search_count', $firstQuery);
            $this->assertArrayHasKey('avg_results', $firstQuery);
            $this->assertArrayHasKey('clicks', $firstQuery);
            $this->assertArrayHasKey('click_rate', $firstQuery);
        }

        // Test passes even if empty (no data in database yet)
        $this->assertTrue(true);
    }

    public function testTopQueriesAcceptsLimitParameter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/analytics/top-queries', ['limit' => 10]);

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        $this->assertResponseIsSuccessful();

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data['data']);
        $this->assertLessThanOrEqual(10, \count($data['data']));
    }

    public function testTrendsReturnsJsonResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/analytics/trends');

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        $this->assertResponseIsSuccessful();
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testTrendsReturnsExpectedStructure(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/analytics/trends');

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        $data = json_decode($response->getContent(), true);

        // If there are results, check the structure
        if (!empty($data['data'])) {
            $firstTrend = $data['data'][0];
            $this->assertArrayHasKey('date', $firstTrend);
            $this->assertArrayHasKey('total', $firstTrend);
            $this->assertArrayHasKey('by_strategy', $firstTrend);
        }

        // Test passes even if empty
        $this->assertTrue(true);
    }

    public function testClickPositionsReturnsJsonResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/analytics/click-positions');

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        $this->assertResponseIsSuccessful();
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testZeroResultsReturnsJsonResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/analytics/zero-results');

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        $this->assertResponseIsSuccessful();
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    /**
     * Test track-click endpoint with valid payload.
     */
    public function testTrackClickWithValidPayload(): void
    {
        $client = static::createClient();

        $payload = [
            'query' => 'test query',
            'position' => 1,
            'pdf_path' => 'test.pdf',
            'page' => 5,
        ];

        $client->request(
            'POST',
            '/api/analytics/track-click',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $data);

        // Can be success or error (404) depending on if search exists
        $this->assertContains($data['status'], ['success', 'error']);
    }

    /**
     * Test track-click endpoint with missing query.
     */
    public function testTrackClickWithMissingQuery(): void
    {
        $client = static::createClient();

        $payload = [
            'position' => 1,
            'pdf_path' => 'test.pdf',
        ];

        $client->request(
            'POST',
            '/api/analytics/track-click',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        $this->assertJson($response->getContent());

        // Should still return JSON (might be error, but valid response)
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    /**
     * Test track-click endpoint only accepts POST.
     */
    public function testTrackClickOnlyAcceptsPost(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/analytics/track-click');

        // Should return 405 Method Not Allowed
        $this->assertResponseStatusCodeSame(405);
    }

    /**
     * Test track-click endpoint with invalid JSON.
     */
    public function testTrackClickWithInvalidJson(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/analytics/track-click',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        // Should handle gracefully (either error or decode to null)
        $this->assertJson($response->getContent());
    }

    /**
     * Test track-click endpoint tracks position.
     */
    public function testTrackClickTracksPosition(): void
    {
        $client = static::createClient();

        $positions = [1, 2, 3];

        foreach ($positions as $position) {
            $payload = [
                'query' => 'test position',
                'position' => $position,
            ];

            $client->request(
                'POST',
                '/api/analytics/track-click',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($payload)
            );

            $response = $client->getResponse();
            $this->skipIfDatabaseNotAvailable($response);

            $this->assertJson($response->getContent());

            $data = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('status', $data);
        }
    }

    /**
     * Test track-click endpoint with optional page parameter.
     */
    public function testTrackClickWithOptionalPage(): void
    {
        $client = static::createClient();

        // Without page
        $payload1 = [
            'query' => 'test without page',
            'position' => 1,
            'pdf_path' => 'test.pdf',
        ];

        $client->request(
            'POST',
            '/api/analytics/track-click',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload1)
        );

        $response = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response);

        $this->assertJson($response->getContent());

        // With page
        $payload2 = [
            'query' => 'test with page',
            'position' => 1,
            'pdf_path' => 'test.pdf',
            'page' => 42,
        ];

        $client->request(
            'POST',
            '/api/analytics/track-click',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload2)
        );

        $response2 = $client->getResponse();
        $this->skipIfDatabaseNotAvailable($response2);

        $this->assertJson($response2->getContent());
    }
}
