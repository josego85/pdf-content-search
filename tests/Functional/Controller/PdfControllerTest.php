<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PdfControllerTest extends WebTestCase
{
    public function testViewerRendersTemplate(): void
    {
        $client = static::createClient();

        $client->request('GET', '/viewer', [
            'path' => 'test.pdf',
            'page' => 1,
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html');
    }

    public function testViewerWithHighlightParameter(): void
    {
        $client = static::createClient();

        $client->request('GET', '/viewer', [
            'path' => 'document.pdf',
            'page' => 5,
            'highlight' => 'search term',
        ]);

        self::assertResponseIsSuccessful();
    }

    public function testViewerWithDefaultPage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/viewer', [
            'path' => 'file.pdf',
        ]);

        self::assertResponseIsSuccessful();
    }

    public function testViewerWithoutParameters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/viewer');

        self::assertResponseIsSuccessful();
    }

    public function testViewerWithAllParameters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/viewer', [
            'path' => 'sample.pdf',
            'page' => 10,
            'highlight' => 'test',
        ]);

        self::assertResponseIsSuccessful();
    }
}
