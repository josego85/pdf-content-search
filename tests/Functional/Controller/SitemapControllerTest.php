<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SitemapControllerTest extends WebTestCase
{
    public function testSitemapReturns200(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/sitemap.xml');

        $this->assertResponseIsSuccessful();
    }

    public function testSitemapReturnsXml(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/sitemap.xml');

        $this->assertResponseHeaderSame('Content-Type', 'application/xml');
    }

    public function testSitemapContainsAbsoluteUrl(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/sitemap.xml');

        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('<loc>http', $content);
        $this->assertStringContainsString('<urlset', $content);
    }

    public function testRobotsReturns200(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/robots.txt');

        $this->assertResponseIsSuccessful();
    }

    public function testRobotsReturnsPlainText(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/robots.txt');

        $this->assertResponseHeaderSame('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function testRobotsContainsAbsoluteSitemapUrl(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/robots.txt');

        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('Sitemap: http', $content);
        $this->assertStringContainsString('/sitemap.xml', $content);
    }
}
