<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\SearchAnalytics;
use App\Message\LogSearchAnalyticsMessage;
use App\MessageHandler\LogSearchAnalyticsHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LogSearchAnalyticsHandler.
 * Tests message handling, IP anonymization, and entity persistence.
 */
final class LogSearchAnalyticsHandlerTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $entityManager;

    private LogSearchAnalyticsHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new LogSearchAnalyticsHandler($this->entityManager);
    }

    public function testInvokeCreatesAndPersistsSearchAnalytics(): void
    {
        $data = [
            'session_id' => 'test-session-123',
            'query' => 'artificial intelligence',
            'search_strategy' => 'hybrid_ai',
            'results_count' => 15,
            'displayed_results_count' => 10,
            'response_time_ms' => 120,
            'user_ip' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0',
            'referer' => 'https://example.com',
        ];

        $message = new LogSearchAnalyticsMessage($data);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static fn (SearchAnalytics $analytics): bool => $analytics->getSessionId() === 'test-session-123'
                && $analytics->getQuery() === 'artificial intelligence'
                && $analytics->getSearchStrategy() === 'hybrid_ai'
                && $analytics->getResultsCount() === 15
                && $analytics->getResponseTimeMs() === 120
                && $analytics->getUserIp() === '192.168.1.0' // Anonymized
                && $analytics->getUserAgent() === 'Mozilla/5.0'
                && $analytics->getReferer() === 'https://example.com'));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        ($this->handler)($message);
    }

    public function testInvokeWithoutOptionalFields(): void
    {
        $data = [
            'session_id' => 'test-session-456',
            'query' => 'test query',
            'search_strategy' => 'exact',
            'results_count' => 5,
            'displayed_results_count' => 5,
            'response_time_ms' => 80,
        ];

        $message = new LogSearchAnalyticsMessage($data);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static fn (SearchAnalytics $analytics): bool => $analytics->getSessionId() === 'test-session-456'
                && $analytics->getQuery() === 'test query'
                && $analytics->getSearchStrategy() === 'exact'
                && $analytics->getResultsCount() === 5
                && $analytics->getResponseTimeMs() === 80
                && $analytics->getUserIp() === null
                && $analytics->getUserAgent() === null
                && $analytics->getReferer() === null));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        ($this->handler)($message);
    }

    public function testInvokeDefaultsSearchStrategyToHybridAi(): void
    {
        $data = [
            'session_id' => 'session-789',
            'query' => 'default strategy',
            'results_count' => 10,
            'displayed_results_count' => 10,
            'response_time_ms' => 100,
        ];

        $message = new LogSearchAnalyticsMessage($data);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static fn (SearchAnalytics $analytics): bool => $analytics->getSearchStrategy() === 'hybrid_ai'));

        $this->entityManager->expects($this->once())->method('flush');

        ($this->handler)($message);
    }

    public function testAnonymizeIpv4(): void
    {
        $data = [
            'session_id' => 'session-ipv4',
            'query' => 'test',
            'search_strategy' => 'hybrid_ai',
            'results_count' => 1,
            'displayed_results_count' => 1,
            'response_time_ms' => 50,
            'user_ip' => '192.168.1.100',
        ];

        $message = new LogSearchAnalyticsMessage($data);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                // IPv4 anonymization: 192.168.1.100 -> 192.168.1.0
                static fn (SearchAnalytics $analytics): bool => $analytics->getUserIp() === '192.168.1.0'
            ));

        $this->entityManager->expects($this->once())->method('flush');

        ($this->handler)($message);
    }

    public function testAnonymizeIpv4WithDifferentOctets(): void
    {
        $testCases = [
            '10.20.30.40' => '10.20.30.0',
            '172.16.254.1' => '172.16.254.0',
            '8.8.8.8' => '8.8.8.0',
            '255.255.255.255' => '255.255.255.0',
        ];

        foreach ($testCases as $originalIp => $expectedIp) {
            $data = [
                'session_id' => 'session-test',
                'query' => 'test',
                'search_strategy' => 'hybrid_ai',
                'results_count' => 1,
                'displayed_results_count' => 1,
                'response_time_ms' => 50,
                'user_ip' => $originalIp,
            ];

            $message = new LogSearchAnalyticsMessage($data);

            $this->entityManager
                ->expects($this->once())
                ->method('persist')
                ->with($this->callback(static fn (SearchAnalytics $analytics): bool => $analytics->getUserIp() === $expectedIp));

            $this->entityManager->expects($this->once())->method('flush');

            $handler = new LogSearchAnalyticsHandler($this->entityManager);
            $handler($message);

            // Reset mock for next iteration
            $this->setUp();
        }
    }

    public function testAnonymizeIpv6(): void
    {
        $data = [
            'session_id' => 'session-ipv6',
            'query' => 'test',
            'search_strategy' => 'hybrid_ai',
            'results_count' => 1,
            'displayed_results_count' => 1,
            'response_time_ms' => 50,
            'user_ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
        ];

        $message = new LogSearchAnalyticsMessage($data);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                // IPv6 anonymization: keep first 48 bits (6 bytes), zero last 80 bits.
                // inet_ntop canonical form removes leading zeros: 2001:db8:85a3::
                static fn (SearchAnalytics $analytics): bool => $analytics->getUserIp() === '2001:db8:85a3::'
            ));

        $this->entityManager->expects($this->once())->method('flush');

        ($this->handler)($message);
    }

    public function testAnonymizeCompressedIpv6(): void
    {
        $data = [
            'session_id' => 'session-ipv6-compressed',
            'query' => 'test',
            'search_strategy' => 'hybrid_ai',
            'results_count' => 1,
            'displayed_results_count' => 1,
            'response_time_ms' => 50,
            'user_ip' => 'fe80::1',
        ];

        $message = new LogSearchAnalyticsMessage($data);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                // fe80::1 full = fe80:0000:0000:0000:0000:0000:0000:0001
                // Keep first 48 bits: fe80:0000:0000 → canonical: fe80::
                static fn (SearchAnalytics $analytics): bool => $analytics->getUserIp() === 'fe80::'
            ));

        $this->entityManager->expects($this->once())->method('flush');

        ($this->handler)($message);
    }

    public function testAnonymizeInvalidIp(): void
    {
        $data = [
            'session_id' => 'session-invalid',
            'query' => 'test',
            'search_strategy' => 'hybrid_ai',
            'results_count' => 1,
            'displayed_results_count' => 1,
            'response_time_ms' => 50,
            'user_ip' => 'invalid-ip-address',
        ];

        $message = new LogSearchAnalyticsMessage($data);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                // Invalid IP becomes 0.0.0.0
                static fn (SearchAnalytics $analytics): bool => $analytics->getUserIp() === '0.0.0.0'
            ));

        $this->entityManager->expects($this->once())->method('flush');

        ($this->handler)($message);
    }

    public function testHandlesWildcardQuery(): void
    {
        $data = [
            'session_id' => 'session-wildcard',
            'query' => 'java*',
            'search_strategy' => 'prefix',
            'results_count' => 8,
            'displayed_results_count' => 8,
            'response_time_ms' => 95,
        ];

        $message = new LogSearchAnalyticsMessage($data);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static fn (SearchAnalytics $analytics): bool => $analytics->getQuery() === 'java*'
                && $analytics->getSearchStrategy() === 'prefix'));

        $this->entityManager->expects($this->once())->method('flush');

        ($this->handler)($message);
    }

    public function testHandlesQuotedQuery(): void
    {
        $data = [
            'session_id' => 'session-quoted',
            'query' => '"artificial intelligence"',
            'search_strategy' => 'exact',
            'results_count' => 3,
            'displayed_results_count' => 3,
            'response_time_ms' => 110,
        ];

        $message = new LogSearchAnalyticsMessage($data);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static fn (SearchAnalytics $analytics): bool => $analytics->getQuery() === '"artificial intelligence"'
                && $analytics->getSearchStrategy() === 'exact'));

        $this->entityManager->expects($this->once())->method('flush');

        ($this->handler)($message);
    }

    public function testHandlesZeroResults(): void
    {
        $data = [
            'session_id' => 'session-zero',
            'query' => 'nonexistent term',
            'search_strategy' => 'hybrid_ai',
            'results_count' => 0,
            'displayed_results_count' => 0,
            'response_time_ms' => 45,
        ];

        $message = new LogSearchAnalyticsMessage($data);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static fn (SearchAnalytics $analytics): bool => $analytics->getResultsCount() === 0));

        $this->entityManager->expects($this->once())->method('flush');

        ($this->handler)($message);
    }
}
