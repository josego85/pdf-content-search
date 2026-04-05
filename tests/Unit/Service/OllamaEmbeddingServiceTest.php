<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\OllamaEmbeddingService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Tests for OllamaEmbeddingService.
 * Validates embedding generation with retry logic and dimension validation.
 */
final class OllamaEmbeddingServiceTest extends TestCase
{
    private const string OLLAMA_HOST = 'http://ollama:11434';
    private const string EMBEDDING_MODEL = 'nomic-embed-text';
    private const int DIMENSIONS = 768;

    private HttpClientInterface&\PHPUnit\Framework\MockObject\MockObject $httpClient;

    private OllamaEmbeddingService $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->service = new OllamaEmbeddingService(
            $this->httpClient,
            self::OLLAMA_HOST,
            self::EMBEDDING_MODEL,
            self::DIMENSIONS,
            -1,
        );
    }

    // -------------------------------------------------------------------------
    // embed() — single text, sends input as plain string
    // -------------------------------------------------------------------------

    public function testEmbedSuccessfully(): void
    {
        $text = 'renewable energy solutions';
        $expectedEmbedding = array_fill(0, self::DIMENSIONS, 0.1);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['embeddings' => [$expectedEmbedding]]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                self::OLLAMA_HOST . '/api/embed',
                $this->callback(static fn ($options): bool => $options['json']['model'] === self::EMBEDDING_MODEL
                    && $options['json']['input'] === $text  // plain string, not array
                    && $options['json']['keep_alive'] === -1
                    && $options['timeout'] === 30)
            )
            ->willReturn($response);

        $result = $this->service->embed($text);

        $this->assertSame($expectedEmbedding, $result);
    }

    public function testEmbedSendsInputAsString(): void
    {
        $text = 'test query';
        $embedding = array_fill(0, self::DIMENSIONS, 0.1);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('toArray')
            ->willReturn(['embeddings' => [$embedding]]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                self::OLLAMA_HOST . '/api/embed',
                [
                    'json' => [
                        'model' => self::EMBEDDING_MODEL,
                        'input' => $text,       // string, not [$text]
                        'keep_alive' => -1,
                    ],
                    'timeout' => 30,
                ]
            )
            ->willReturn($response);

        $this->service->embed($text);
    }

    public function testEmbedThrowsExceptionForInvalidDimensions(): void
    {
        $text = 'test query';
        $wrongDimensionEmbedding = array_fill(0, 512, 0.1);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('toArray')
            ->willReturn(['embeddings' => [$wrongDimensionEmbedding]]);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected 768 dimensions, got 512');

        $this->service->embed($text);
    }

    public function testEmbedThrowsExceptionForMissingEmbeddingField(): void
    {
        $text = 'test query';

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('toArray')
            ->willReturn(['error' => 'Model not found']);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid response from Ollama: missing or invalid embeddings field');

        $this->service->embed($text);
    }

    public function testEmbedRetriesOnTransientFailure(): void
    {
        $text = 'test query';
        $expectedEmbedding = array_fill(0, self::DIMENSIONS, 0.1);

        $failureResponse = $this->createMock(ResponseInterface::class);
        // \Exception (not \RuntimeException) simulates a transient network error — retried.
        $failureResponse
            ->method('toArray')
            ->willThrowException(new \Exception('Connection timeout'));

        $successResponse = $this->createMock(ResponseInterface::class);
        $successResponse
            ->method('toArray')
            ->willReturn(['embeddings' => [$expectedEmbedding]]);

        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($failureResponse, $successResponse);

        $result = $this->service->embed($text);

        $this->assertSame($expectedEmbedding, $result);
    }

    public function testEmbedThrowsExceptionAfterMaxRetries(): void
    {
        $text = 'test query';

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('toArray')
            ->willThrowException(new \Exception('Connection failed'));

        $this->httpClient
            ->expects($this->exactly(3)) // MAX_RETRIES = 3
            ->method('request')
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate embedding after 3 attempts');

        $this->service->embed($text);
    }

    public function testEmbedDoesNotRetryOnPermanentRuntimeException(): void
    {
        $text = 'test query';

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('toArray')
            ->willThrowException(new \RuntimeException('Permanent validation failure'));

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Permanent validation failure');

        $this->service->embed($text);
    }

    public function testEmbedThrowsExceptionForInvalidEmbeddingType(): void
    {
        $text = 'test query';

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('toArray')
            ->willReturn(['embeddings' => 'not_an_array']);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid response from Ollama: missing or invalid embeddings field');

        $this->service->embed($text);
    }

    // -------------------------------------------------------------------------
    // embedConcurrentBatches() — fires all HTTP requests before blocking
    // -------------------------------------------------------------------------

    public function testEmbedConcurrentBatchesFiresAllRequestsBeforeBlocking(): void
    {
        $batch0 = ['text0a', 'text0b'];
        $batch1 = ['text1a', 'text1b'];
        $embedding = array_fill(0, self::DIMENSIONS, 0.1);

        $response0 = $this->createMock(ResponseInterface::class);
        $response0
            ->method('toArray')
            ->willReturn(['embeddings' => [$embedding, $embedding]]);

        $response1 = $this->createMock(ResponseInterface::class);
        $response1
            ->method('toArray')
            ->willReturn(['embeddings' => [$embedding, $embedding]]);

        // Both requests are fired before either toArray() is called.
        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($response0, $response1);

        $results = $this->service->embedConcurrentBatches([0 => $batch0, 1 => $batch1]);

        $this->assertArrayHasKey(0, $results);
        $this->assertArrayHasKey(1, $results);
        $this->assertCount(2, $results[0]);
        $this->assertCount(2, $results[1]);
        $this->assertSame($embedding, $results[0][0]);
        $this->assertSame($embedding, $results[1][0]);
    }

    public function testEmbedConcurrentBatchesWithEmptyArray(): void
    {
        $this->httpClient->expects($this->never())->method('request');

        $results = $this->service->embedConcurrentBatches([]);

        $this->assertSame([], $results);
    }

    public function testEmbedConcurrentBatchesSkipsEmptyBatches(): void
    {
        $batch0 = ['text0'];
        $embedding = array_fill(0, self::DIMENSIONS, 0.1);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('toArray')
            ->willReturn(['embeddings' => [$embedding]]);

        // Empty batch at index 1 is skipped — only one request fired.
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $results = $this->service->embedConcurrentBatches([0 => $batch0, 1 => []]);

        $this->assertArrayHasKey(0, $results);
        $this->assertArrayNotHasKey(1, $results);
        $this->assertCount(1, $results[0]);
    }

    public function testEmbedConcurrentBatchesThrowsOnInvalidDimensions(): void
    {
        $batch0 = ['text0'];
        $badEmbedding = array_fill(0, 512, 0.1);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('toArray')
            ->willReturn(['embeddings' => [$badEmbedding]]);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Concurrent batch 0: expected 768 dimensions at index 0, got 512');

        $this->service->embedConcurrentBatches([0 => $batch0]);
    }

    public function testEmbedConcurrentBatchesThrowsOnMissingEmbeddings(): void
    {
        $batch0 = ['text0'];

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('toArray')
            ->willReturn(['error' => 'model not found']);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Concurrent batch 0: invalid response from Ollama');

        $this->service->embedConcurrentBatches([0 => $batch0]);
    }

    // -------------------------------------------------------------------------
    // getDimensions()
    // -------------------------------------------------------------------------

    public function testGetDimensions(): void
    {
        $this->assertSame(self::DIMENSIONS, $this->service->getDimensions());
    }
}
