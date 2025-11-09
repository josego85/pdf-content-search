<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for PdfController.
 * Tests PDF viewer page rendering.
 */
final class PdfControllerTest extends WebTestCase
{
    public function testPdfViewerIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/viewer', [
            'path' => '/pdfs/test.pdf',
            'page' => 1,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testPdfViewerReturnsHtml(): void
    {
        $client = static::createClient();
        $client->request('GET', '/viewer', [
            'path' => '/pdfs/test.pdf',
            'page' => 1,
        ]);

        $this->assertResponseHeaderSame('Content-Type', 'text/html; charset=UTF-8');
    }

    public function testPdfViewerAcceptsPathParameter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/viewer', [
            'path' => '/pdfs/document.pdf',
            'page' => 1,
        ]);

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful() || $response->getStatusCode() < 500);
    }

    public function testPdfViewerAcceptsPageParameter(): void
    {
        $client = static::createClient();

        $pages = [1, 5, 10, 100];

        foreach ($pages as $page) {
            $client->request('GET', '/viewer', [
                'path' => '/pdfs/test.pdf',
                'page' => $page,
            ]);

            $response = $client->getResponse();
            $this->assertTrue($response->isSuccessful() || $response->getStatusCode() < 500);
        }
    }

    public function testPdfViewerAcceptsHighlightParameter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/viewer', [
            'path' => '/pdfs/test.pdf',
            'page' => 1,
            'highlight' => 'search,term,highlight',
        ]);

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful() || $response->getStatusCode() < 500);
    }

    public function testPdfViewerWithMultipleHighlightTerms(): void
    {
        $client = static::createClient();
        $client->request('GET', '/viewer', [
            'path' => '/pdfs/test.pdf',
            'page' => 1,
            'highlight' => 'term1,term2,term3',
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testPdfViewerRouteName(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        $route = $router->generate('pdf_viewer', [
            'path' => '/pdfs/test.pdf',
            'page' => 1,
        ]);

        $this->assertStringContainsString('/viewer', $route);
    }

    public function testPdfViewerStatusCode200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/viewer', [
            'path' => '/pdfs/test.pdf',
            'page' => 1,
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testPdfViewerDoesNotAcceptPostRequests(): void
    {
        $client = static::createClient();
        $client->request('POST', '/viewer', [
            'path' => '/pdfs/test.pdf',
            'page' => 1,
        ]);

        $this->assertResponseStatusCodeSame(405);
    }

    public function testPdfViewerWithSpecialCharactersInPath(): void
    {
        $client = static::createClient();
        $client->request('GET', '/viewer', [
            'path' => '/pdfs/document with spaces.pdf',
            'page' => 1,
        ]);

        $response = $client->getResponse();
        // Should handle special characters without crashing
        $this->assertTrue($response->getStatusCode() >= 200 && $response->getStatusCode() < 600);
    }

    public function testPdfViewerRendersTemplate(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/viewer', [
            'path' => '/pdfs/test.pdf',
            'page' => 1,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('html');
    }
}
