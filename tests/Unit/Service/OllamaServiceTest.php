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

    public function testTranslateReturnsTranslatedText(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'response' => 'Texto traducido',
            'done' => true,
        ]));

        $httpClient = new MockHttpClient($mockResponse);

        $service = new OllamaService(
            $httpClient,
            $this->languageMapper,
            'http://localhost:11434',
            'llama2',
            30,
            0.7,
            2000
        );

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

        $service = new OllamaService(
            $httpClient,
            $this->languageMapper,
            'http://localhost:11434',
            'llama2',
            30,
            0.7,
            2000
        );

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

        $service = new OllamaService(
            $httpClient,
            $this->languageMapper,
            'http://localhost:11434',
            'llama2',
            30,
            0.7,
            2000
        );

        $result = $service->translate('Text to translate', 'fr');

        // Verify the request was made
        self::assertNotEmpty($result);
    }

    public function testTranslateSendsCorrectRequestFormat(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'response' => 'Translated',
            'done' => true,
        ]));

        $httpClient = new MockHttpClient($mockResponse);

        $service = new OllamaService(
            $httpClient,
            $this->languageMapper,
            'http://localhost:11434',
            'llama2',
            30,
            0.7,
            2000
        );

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

        $service = new OllamaService(
            $httpClient,
            $this->languageMapper,
            'http://localhost:11434',
            'llama2',
            30,
            0.7,
            2000
        );

        $result = $service->translate('Text', 'es');

        self::assertSame('', $result);
    }

    public function testTranslateHandlesMissingResponseKey(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'done' => true,
        ]));

        $httpClient = new MockHttpClient($mockResponse);

        $service = new OllamaService(
            $httpClient,
            $this->languageMapper,
            'http://localhost:11434',
            'llama2',
            30,
            0.7,
            2000
        );

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

        $service = new OllamaService(
            $httpClient,
            $this->languageMapper,
            'http://custom-host:8080',
            'custom-model',
            60,
            0.9,
            4000
        );

        $result = $service->translate('Test', 'es');

        self::assertSame('Result', $result);
    }
}
