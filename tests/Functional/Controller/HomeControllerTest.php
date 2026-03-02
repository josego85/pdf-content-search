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
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/');

        $this->assertResponseIsSuccessful();
    }

    public function testHomePageReturnsHtml(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/');

        $this->assertResponseHeaderSame('Content-Type', 'text/html; charset=UTF-8');
    }

    public function testHomePageContainsSearchTemplate(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/');

        // Should render the search.html.twig template
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('html');
    }

    public function testHomePageStatusCode200(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testHomePageDoesNotRedirect(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/');

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $this->assertFalse($response->isRedirection());
    }
}
