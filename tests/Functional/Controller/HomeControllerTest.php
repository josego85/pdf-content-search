<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for HomeController.
 * Tests the main search page rendering.
 */
final class HomeControllerTest extends WebTestCase
{
    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testHomePageReturnsHtml(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseHeaderSame('Content-Type', 'text/html; charset=UTF-8');
    }

    public function testHomePageContainsSearchTemplate(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        // Should render the search.html.twig template
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('html');
    }

    public function testHomePageStatusCode200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testHomePageDoesNotRedirect(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $this->assertFalse($response->isRedirection());
    }
}
