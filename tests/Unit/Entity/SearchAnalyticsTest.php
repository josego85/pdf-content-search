<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\SearchAnalytics;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SearchAnalytics entity.
 * Tests all getters, setters, and fluent interface.
 */
final class SearchAnalyticsTest extends TestCase
{
    public function testConstructorSetsCreatedAt(): void
    {
        $entity = new SearchAnalytics();

        $this->assertInstanceOf(\DateTimeInterface::class, $entity->getCreatedAt());
        $this->assertEqualsWithDelta(time(), $entity->getCreatedAt()->getTimestamp(), 2);
    }

    public function testIdIsNullByDefault(): void
    {
        $entity = new SearchAnalytics();

        $this->assertNull($entity->getId());
    }

    public function testSessionIdGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $sessionId = 'test-session-123';

        $result = $entity->setSessionId($sessionId);

        $this->assertSame($entity, $result); // Fluent interface
        $this->assertSame($sessionId, $entity->getSessionId());
    }

    public function testQueryGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $query = 'artificial intelligence';

        $result = $entity->setQuery($query);

        $this->assertSame($entity, $result);
        $this->assertSame($query, $entity->getQuery());
    }

    public function testSearchStrategyGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $strategy = 'hybrid_ai';

        $result = $entity->setSearchStrategy($strategy);

        $this->assertSame($entity, $result);
        $this->assertSame($strategy, $entity->getSearchStrategy());
    }

    public function testResultsCountGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $count = 42;

        $result = $entity->setResultsCount($count);

        $this->assertSame($entity, $result);
        $this->assertSame($count, $entity->getResultsCount());
    }

    public function testResponseTimeMsGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $time = 150;

        $result = $entity->setResponseTimeMs($time);

        $this->assertSame($entity, $result);
        $this->assertSame($time, $entity->getResponseTimeMs());
    }

    public function testClickedDefaultsToFalse(): void
    {
        $entity = new SearchAnalytics();

        $this->assertFalse($entity->isClicked());
    }

    public function testClickedGetterSetter(): void
    {
        $entity = new SearchAnalytics();

        $result = $entity->setClicked(true);

        $this->assertSame($entity, $result);
        $this->assertTrue($entity->isClicked());

        $entity->setClicked(false);
        $this->assertFalse($entity->isClicked());
    }

    public function testClickedPositionGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $position = 3;

        $this->assertNull($entity->getClickedPosition());

        $result = $entity->setClickedPosition($position);

        $this->assertSame($entity, $result);
        $this->assertSame($position, $entity->getClickedPosition());

        $entity->setClickedPosition(null);
        $this->assertNull($entity->getClickedPosition());
    }

    public function testClickedPdfGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $pdf = 'document.pdf';

        $this->assertNull($entity->getClickedPdf());

        $result = $entity->setClickedPdf($pdf);

        $this->assertSame($entity, $result);
        $this->assertSame($pdf, $entity->getClickedPdf());

        $entity->setClickedPdf(null);
        $this->assertNull($entity->getClickedPdf());
    }

    public function testClickedPageGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $page = 5;

        $this->assertNull($entity->getClickedPage());

        $result = $entity->setClickedPage($page);

        $this->assertSame($entity, $result);
        $this->assertSame($page, $entity->getClickedPage());

        $entity->setClickedPage(null);
        $this->assertNull($entity->getClickedPage());
    }

    public function testTimeToClickMsGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $time = 2500;

        $this->assertNull($entity->getTimeToClickMs());

        $result = $entity->setTimeToClickMs($time);

        $this->assertSame($entity, $result);
        $this->assertSame($time, $entity->getTimeToClickMs());

        $entity->setTimeToClickMs(null);
        $this->assertNull($entity->getTimeToClickMs());
    }

    public function testUserIpGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $ip = '192.168.1.1';

        $this->assertNull($entity->getUserIp());

        $result = $entity->setUserIp($ip);

        $this->assertSame($entity, $result);
        $this->assertSame($ip, $entity->getUserIp());

        $entity->setUserIp(null);
        $this->assertNull($entity->getUserIp());
    }

    public function testUserAgentGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

        $this->assertNull($entity->getUserAgent());

        $result = $entity->setUserAgent($userAgent);

        $this->assertSame($entity, $result);
        $this->assertSame($userAgent, $entity->getUserAgent());

        $entity->setUserAgent(null);
        $this->assertNull($entity->getUserAgent());
    }

    public function testRefererGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $referer = 'https://example.com/search';

        $this->assertNull($entity->getReferer());

        $result = $entity->setReferer($referer);

        $this->assertSame($entity, $result);
        $this->assertSame($referer, $entity->getReferer());

        $entity->setReferer(null);
        $this->assertNull($entity->getReferer());
    }

    public function testCreatedAtGetterSetter(): void
    {
        $entity = new SearchAnalytics();
        $date = new \DateTime('2025-01-01 12:00:00');

        $result = $entity->setCreatedAt($date);

        $this->assertSame($entity, $result);
        $this->assertSame($date, $entity->getCreatedAt());
    }

    public function testFluentInterface(): void
    {
        $entity = new SearchAnalytics();

        $result = $entity
            ->setSessionId('session-123')
            ->setQuery('test query')
            ->setSearchStrategy('hybrid_ai')
            ->setResultsCount(10)
            ->setResponseTimeMs(100)
            ->setClicked(true)
            ->setClickedPosition(1)
            ->setClickedPdf('test.pdf')
            ->setClickedPage(5)
            ->setTimeToClickMs(1500)
            ->setUserIp('127.0.0.1')
            ->setUserAgent('TestAgent')
            ->setReferer('http://localhost');

        $this->assertSame($entity, $result);
        $this->assertSame('session-123', $entity->getSessionId());
        $this->assertSame('test query', $entity->getQuery());
        $this->assertSame('hybrid_ai', $entity->getSearchStrategy());
        $this->assertSame(10, $entity->getResultsCount());
        $this->assertSame(100, $entity->getResponseTimeMs());
        $this->assertTrue($entity->isClicked());
        $this->assertSame(1, $entity->getClickedPosition());
        $this->assertSame('test.pdf', $entity->getClickedPdf());
        $this->assertSame(5, $entity->getClickedPage());
        $this->assertSame(1500, $entity->getTimeToClickMs());
        $this->assertSame('127.0.0.1', $entity->getUserIp());
        $this->assertSame('TestAgent', $entity->getUserAgent());
        $this->assertSame('http://localhost', $entity->getReferer());
    }
}
