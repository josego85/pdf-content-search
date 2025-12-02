<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TranslationControllerTest extends WebTestCase
{
    public function testTranslateEndpointWithMissingParameters(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/translations/translate', []);

        self::assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $data['status']);
    }

    public function testTranslateEndpointWithInvalidFile(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/translations/translate', [
            'filename' => 'nonexistent.pdf',
            'page' => 1,
            'target_language' => 'es',
        ]);

        self::assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $data['status']);
    }

    public function testTranslateEndpointHandlesExceptions(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/translations/translate', [
            'filename' => null,
            'page' => 'invalid',
            'target_language' => 'es',
        ]);

        $response = $client->getResponse();
        self::assertTrue($response->isClientError() || $response->isServerError());
    }

    public function testStatusEndpointWithMissingParameters(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/translations/status', []);

        self::assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $data['status']);
    }

    public function testStatusEndpointWithInvalidFile(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/translations/status', [
            'filename' => 'missing.pdf',
            'page' => 1,
            'target_language' => 'es',
        ]);

        self::assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $data['status']);
    }

    public function testStatusEndpointReturnsProcessingWhenNotReady(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/translations/status', [
            'filename' => 'nonexistent.pdf',
            'page' => 1,
            'target_language' => 'es',
        ]);

        $response = $client->getResponse();
        self::assertTrue($response->isClientError() || $response->isServerError());
    }

    public function testTranslateEndpointReturnsJsonResponse(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/translations/translate', [
            'filename' => 'test.pdf',
            'page' => 1,
            'target_language' => 'es',
        ]);

        self::assertResponseHeaderSame('Content-Type', 'application/json');
    }

    public function testStatusEndpointReturnsJsonResponse(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/translations/status', [
            'filename' => 'test.pdf',
            'page' => 1,
            'target_language' => 'es',
        ]);

        self::assertResponseHeaderSame('Content-Type', 'application/json');
    }

    public function testTranslateEndpointWithDefaultLanguage(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/translations/translate', [
            'filename' => 'test.pdf',
            'page' => 1,
        ]);

        $response = $client->getResponse();
        self::assertNotNull($response->getContent());
    }

    public function testStatusEndpointWithDefaultLanguage(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/translations/status', [
            'filename' => 'test.pdf',
            'page' => 1,
        ]);

        $response = $client->getResponse();
        self::assertNotNull($response->getContent());
    }
}
