<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\LanguageMapper;
use App\Service\OllamaService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OllamaServiceTest extends TestCase
{
    private LanguageMapper $languageMapper;

    protected function setUp(): void
    {
        $this->languageMapper = new LanguageMapper();
    }

    private function makeService(
        MockHttpClient $httpClient,
        string $host = 'http://localhost:11434',
        string $model = 'llama2',
        int $timeout = 30,
        float $temperature = 0.7,
        int $maxTokens = 2000,
        int $numThreads = 12,
        int $keepAlive = -1,
        int $numCtx = 4096,
    ): OllamaService {
        return new OllamaService(
            $httpClient,
            $this->languageMapper,
            $host,
            $model,
            $timeout,
            $temperature,
            $maxTokens,
            $numThreads,
            $keepAlive,
            $numCtx,
        );
    }

    public function testTranslateReturnsTranslatedText(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'response' => 'Texto traducido',
            'done' => true,
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $service = $this->makeService($httpClient);

        $result = $service->translate('Original text', 'es');

        self::assertSame('Texto traducido', $result);
    }

    public function testTranslateTrimsWhitespace(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'response' => '  Translated with spaces  ',
            'done' => true,
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $service = $this->makeService($httpClient);

        $result = $service->translate('Text', 'es');

        self::assertSame('Translated with spaces', $result);
    }

    public function testTranslateUsesLanguageMapper(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'response' => 'French translation',
            'done' => true,
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $service = $this->makeService($httpClient);

        $result = $service->translate('Text to translate', 'fr');

        self::assertNotEmpty($result);
    }

    public function testTranslateSendsCorrectRequestFormat(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'response' => 'Translated',
            'done' => true,
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $service = $this->makeService($httpClient);

        $result = $service->translate('Test', 'es');

        self::assertSame('Translated', $result);
    }

    public function testTranslateHandlesEmptyResponse(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'response' => '',
            'done' => true,
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $service = $this->makeService($httpClient);

        $result = $service->translate('Text', 'es');

        self::assertSame('', $result);
    }

    public function testTranslateHandlesMissingResponseKey(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'done' => true,
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $service = $this->makeService($httpClient);

        $result = $service->translate('Text', 'es');

        self::assertSame('', $result);
    }

    public function testTranslateUsesConfiguredParameters(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'response' => 'Result',
            'done' => true,
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $service = $this->makeService(
            $httpClient,
            host: 'http://custom-host:8080',
            model: 'custom-model',
            timeout: 60,
            temperature: 0.9,
            maxTokens: 4000,
            numThreads: 8,
            keepAlive: 300,
            numCtx: 2048,
        );

        $result = $service->translate('Test', 'es');

        self::assertSame('Result', $result);
    }
}
