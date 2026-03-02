<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PdfControllerTest extends WebTestCase
{
    public function testViewerRendersTemplate(): void
    {
        $client = self::createClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/viewer', [
            'path' => 'test.pdf',
            'page' => 1,
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html');
    }

    public function testViewerWithHighlightParameter(): void
    {
        $client = self::createClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/viewer', [
            'path' => 'document.pdf',
            'page' => 5,
            'highlight' => 'search term',
        ]);

        self::assertResponseIsSuccessful();
    }

    public function testViewerWithDefaultPage(): void
    {
        $client = self::createClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/viewer', [
            'path' => 'file.pdf',
        ]);

        self::assertResponseIsSuccessful();
    }

    public function testViewerWithoutParameters(): void
    {
        $client = self::createClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/viewer');

        self::assertResponseIsSuccessful();
    }

    public function testViewerWithAllParameters(): void
    {
        $client = self::createClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/viewer', [
            'path' => 'sample.pdf',
            'page' => 10,
            'highlight' => 'test',
        ]);

        self::assertResponseIsSuccessful();
    }
}
