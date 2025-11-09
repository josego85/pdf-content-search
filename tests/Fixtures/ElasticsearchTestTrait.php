<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Provides helper methods for mocking Elasticsearch Client in tests.
 * Reduces code duplication and ensures consistent mocking patterns.
 */
trait ElasticsearchTestTrait
{
    /**
     * Creates a mock Elasticsearch Client.
     */
    protected function createElasticsearchClientMock(): MockObject|Client
    {
        return $this->createMock(Client::class);
    }

    /**
     * Creates a mock Elasticsearch response with the given data.
     */
    protected function createElasticsearchResponse(array $data): ElasticsearchResponse
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($data));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(200);

        return new ElasticsearchResponse($response);
    }

    /**
     * Creates a mock for a successful Elasticsearch operation.
     */
    protected function createSuccessResponse(array $additionalData = []): ElasticsearchResponse
    {
        $data = array_merge([
            'acknowledged' => true,
        ], $additionalData);

        return $this->createElasticsearchResponse($data);
    }

    /**
     * Creates a mock for a search response.
     */
    protected function createSearchResponse(array $hits = []): ElasticsearchResponse
    {
        $data = [
            'took' => 5,
            'timed_out' => false,
            '_shards' => [
                'total' => 1,
                'successful' => 1,
                'skipped' => 0,
                'failed' => 0,
            ],
            'hits' => [
                'total' => [
                    'value' => count($hits),
                    'relation' => 'eq',
                ],
                'max_score' => count($hits) > 0 ? ($hits[0]['_score'] ?? 1.0) : 0,
                'hits' => $hits,
            ],
        ];

        return $this->createElasticsearchResponse($data);
    }

    /**
     * Creates a mock for an index exists response.
     */
    protected function createIndexExistsResponse(bool $exists): ElasticsearchResponse
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn('');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn($exists ? 200 : 404);

        return new ElasticsearchResponse($response);
    }

    /**
     * Configures the client mock to expect a search call.
     */
    protected function expectSearchCall(
        MockObject $client,
        array $expectedQuery,
        ElasticsearchResponse $response
    ): void {
        $client->expects($this->once())
            ->method('search')
            ->with($this->callback(static function ($params) {
                // Verify the query structure
                return isset($params['index'])
                    && isset($params['body']);
            }))
            ->willReturn($response);
    }

    /**
     * Configures the client mock to expect an index call.
     */
    protected function expectIndexCall(
        MockObject $client,
        string $expectedIndex,
        ElasticsearchResponse $response
    ): void {
        $client->expects($this->once())
            ->method('index')
            ->with($this->callback(static function ($params) use ($expectedIndex) {
                return $params['index'] === $expectedIndex
                    && isset($params['id'])
                    && isset($params['body']);
            }))
            ->willReturn($response);
    }

    /**
     * Configures the client mock to expect a create index call.
     */
    protected function expectCreateIndexCall(
        MockObject $client,
        ElasticsearchResponse $response
    ): void {
        $client->method('indices')->willReturnSelf();
        $client->expects($this->once())
            ->method('create')
            ->willReturn($response);
    }

    /**
     * Configures the client mock to expect a delete index call.
     */
    protected function expectDeleteIndexCall(
        MockObject $client,
        ElasticsearchResponse $response
    ): void {
        $client->method('indices')->willReturnSelf();
        $client->expects($this->once())
            ->method('delete')
            ->willReturn($response);
    }
}
