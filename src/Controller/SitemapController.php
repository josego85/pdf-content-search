<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function sitemap(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();

        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
              <url>
                <loc>{$baseUrl}/</loc>
                <changefreq>weekly</changefreq>
                <priority>1.0</priority>
              </url>
            </urlset>
            XML;

        return new Response($xml, Response::HTTP_OK, [
            'Content-Type' => 'application/xml',
        ]);
    }

    #[Route('/robots.txt', name: 'robots', methods: ['GET'])]
    public function robots(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();

        $content = <<<TXT
            User-agent: *
            Allow: /

            Disallow: /api/
            Disallow: /analytics
            Disallow: /translate/
            Disallow: /viewer

            Sitemap: {$baseUrl}/sitemap.xml
            TXT;

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
