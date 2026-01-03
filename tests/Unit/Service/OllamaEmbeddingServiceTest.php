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
    private const OLLAMA_HOST = 'http://ollama:11434';
    private const EMBEDDING_MODEL = 'nomic-embed-text';
    private const DIMENSIONS = 768;

    private HttpClientInterface $httpClient;

    private OllamaEmbeddingService $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->service = new OllamaEmbeddingService(
            $this->httpClient,
            self::OLLAMA_HOST,
            self::EMBEDDING_MODEL,
            self::DIMENSIONS
        );
    }

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
                $this->callback(static function ($options) use ($text) {
                    return $options['json']['model'] === self::EMBEDDING_MODEL
                        && $options['json']['input'] === $text
                        && $options['timeout'] === 30;
                })
            )
            ->willReturn($response);

        $result = $this->service->embed($text);

        $this->assertSame($expectedEmbedding, $result);
    }

    public function testEmbedThrowsExceptionForInvalidDimensions(): void
    {
        $text = 'test query';
        $wrongDimensionEmbedding = array_fill(0, 512, 0.1); // Wrong dimensions

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
            ->willReturn(['error' => 'Model not found']); // Missing 'embeddings' field

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid response from Ollama: missing or invalid embeddings field');

        $this->service->embed($text);
    }

    public function testEmbedRetriesOnFailure(): void
    {
        $text = 'test query';
        $expectedEmbedding = array_fill(0, self::DIMENSIONS, 0.1);

        $failureResponse = $this->createMock(ResponseInterface::class);
        $failureResponse
            ->method('toArray')
            ->willThrowException(new \RuntimeException('Connection timeout'));

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
            ->willThrowException(new \RuntimeException('Connection failed'));

        $this->httpClient
            ->expects($this->exactly(3)) // MAX_RETRIES = 3
            ->method('request')
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate embedding after 3 attempts');

        $this->service->embed($text);
    }

    public function testEmbedBatchProcessesMultipleTexts(): void
    {
        $texts = [
            'renewable energy',
            'solar power',
            'wind turbines',
        ];
        $embedding = array_fill(0, self::DIMENSIONS, 0.1);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('toArray')
            ->willReturn(['embeddings' => [$embedding]]);

        $this->httpClient
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturn($response);

        $results = $this->service->embedBatch($texts);

        $this->assertCount(3, $results);
        $this->assertSame($embedding, $results[0]);
        $this->assertSame($embedding, $results[1]);
        $this->assertSame($embedding, $results[2]);
    }

    public function testGetDimensions(): void
    {
        $this->assertSame(self::DIMENSIONS, $this->service->getDimensions());
    }

    public function testEmbedThrowsExceptionForInvalidEmbeddingType(): void
    {
        $text = 'test query';

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('toArray')
            ->willReturn(['embeddings' => 'not_an_array']); // Invalid type

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid response from Ollama: missing or invalid embeddings field');

        $this->service->embed($text);
    }

    public function testEmbedBatchWithEmptyArray(): void
    {
        $results = $this->service->embedBatch([]);

        $this->assertSame([], $results);
    }

    public function testEmbedBatchWithSingleText(): void
    {
        $texts = ['single text'];
        $embedding = array_fill(0, self::DIMENSIONS, 0.1);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('toArray')
            ->willReturn(['embeddings' => [$embedding]]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $results = $this->service->embedBatch($texts);

        $this->assertCount(1, $results);
        $this->assertSame($embedding, $results[0]);
    }

    public function testEmbedSendsCorrectRequestParameters(): void
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
                        'input' => $text,
                    ],
                    'timeout' => 30,
                ]
            )
            ->willReturn($response);

        $this->service->embed($text);
    }
}
