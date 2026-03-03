<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\SearchAnalyticsRepository;
use App\Service\AnalyticsService;
use PHPUnit\Framework\TestCase;

final class AnalyticsServiceTest extends TestCase
{
    private SearchAnalyticsRepository&\PHPUnit\Framework\MockObject\MockObject $repository;

    private AnalyticsService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SearchAnalyticsRepository::class);
        $this->service = new AnalyticsService($this->repository);
    }

    // -----------------------------------------------------------------------
    // getOverview
    // -----------------------------------------------------------------------

    public function testGetOverviewFormatsMetrics(): void
    {
        $this->repository->method('getOverviewMetrics')->willReturn([
            'totalSearches' => '100',
            'uniqueSessions' => '40',
            'avgResponseTime' => '250.5',
            'zeroResultSearches' => '10',
            'clickedSearches' => '50',
        ]);

        $result = $this->service->getOverview(7);

        $this->assertSame(100, $result['total_searches']);
        $this->assertSame(40, $result['unique_sessions']);
        $this->assertSame(251.0, $result['avg_response_time_ms']);
        $this->assertSame(10, $result['zero_result_searches']);
        $this->assertSame(50.0, $result['click_through_rate']);  // 50/100 * 100
        $this->assertSame(90.0, $result['success_rate']);         // 90/100 * 100
        $this->assertSame(7, $result['period']['days']);
    }

    public function testGetOverviewWithZeroSearchesReturnsZeroRates(): void
    {
        $this->repository->method('getOverviewMetrics')->willReturn([
            'totalSearches' => '0',
            'uniqueSessions' => '0',
            'avgResponseTime' => '0',
            'zeroResultSearches' => '0',
            'clickedSearches' => '0',
        ]);

        $result = $this->service->getOverview(7);

        $this->assertSame(0, $result['click_through_rate']);
        $this->assertSame(0, $result['success_rate']);
    }

    // -----------------------------------------------------------------------
    // getTopQueries
    // -----------------------------------------------------------------------

    public function testGetTopQueriesFormatsClickRate(): void
    {
        $this->repository->method('getTopQueries')->willReturn([
            ['query' => 'php', 'searchCount' => '10', 'avgResults' => '5.5', 'clicks' => '3'],
            ['query' => 'no clicks', 'searchCount' => '5', 'avgResults' => '2.0', 'clicks' => '0'],
        ]);

        $result = $this->service->getTopQueries(7, 20);

        $this->assertCount(2, $result);

        $this->assertSame('php', $result[0]['query']);
        $this->assertSame(10, $result[0]['search_count']);
        $this->assertSame(5.5, $result[0]['avg_results']);
        $this->assertSame(3, $result[0]['clicks']);
        $this->assertSame(30.0, $result[0]['click_rate']); // 3/10 * 100

        $this->assertSame(0.0, $result[1]['click_rate']); // round() always returns float
    }

    public function testGetTopQueriesReturnsEmptyArrayWhenNoData(): void
    {
        $this->repository->method('getTopQueries')->willReturn([]);

        $result = $this->service->getTopQueries(7, 20);

        $this->assertSame([], $result);
    }

    // -----------------------------------------------------------------------
    // getTrends
    // -----------------------------------------------------------------------

    public function testGetTrendsGroupsByDateAndStrategy(): void
    {
        $this->repository->method('getSearchTrends')->willReturn([
            ['date' => '2026-01-01', 'searchStrategy' => 'hybrid_ai', 'searchCount' => '5'],
            ['date' => '2026-01-01', 'searchStrategy' => 'exact', 'searchCount' => '3'],
            ['date' => '2026-01-02', 'searchStrategy' => 'hybrid_ai', 'searchCount' => '7'],
        ]);

        $result = $this->service->getTrends(7);

        $this->assertCount(2, $result);

        $this->assertSame('2026-01-01', $result[0]['date']);
        $this->assertSame(8, $result[0]['total']);
        $this->assertSame(5, $result[0]['by_strategy']['hybrid_ai']);
        $this->assertSame(3, $result[0]['by_strategy']['exact']);

        $this->assertSame('2026-01-02', $result[1]['date']);
        $this->assertSame(7, $result[1]['total']);
    }

    public function testGetTrendsHandlesDateTimeInterface(): void
    {
        $date = new \DateTime('2026-01-01');

        $this->repository->method('getSearchTrends')->willReturn([
            ['date' => $date, 'searchStrategy' => 'exact', 'searchCount' => '4'],
        ]);

        $result = $this->service->getTrends(7);

        $this->assertSame('2026-01-01', $result[0]['date']);
    }

    public function testGetTrendsReturnsEmptyArrayWhenNoData(): void
    {
        $this->repository->method('getSearchTrends')->willReturn([]);

        $result = $this->service->getTrends(7);

        $this->assertSame([], $result);
    }

    // -----------------------------------------------------------------------
    // buildExportRows
    // -----------------------------------------------------------------------

    public function testBuildExportRowsTopQueriesType(): void
    {
        $this->repository->method('getTopQueries')->willReturn([
            ['query' => 'test', 'searchCount' => '5', 'avgResults' => '3.0', 'clicks' => '2'],
        ]);

        [$rows, $headers] = $this->service->buildExportRows('top-queries', 7);

        $this->assertSame(['Query', 'Searches', 'Avg Results', 'Clicks', 'Click Rate (%)'], $headers);
        $this->assertCount(1, $rows);
        $this->assertSame('test', $rows[0][0]);
        $this->assertSame(5, $rows[0][1]);
    }

    public function testBuildExportRowsTrendsType(): void
    {
        $this->repository->method('getSearchTrends')->willReturn([
            ['date' => '2026-01-01', 'searchStrategy' => 'hybrid_ai', 'searchCount' => '3'],
        ]);

        [$rows, $headers] = $this->service->buildExportRows('trends', 7);

        $this->assertSame(['Date', 'Total', 'Hybrid AI', 'Exact', 'Prefix'], $headers);
        $this->assertCount(1, $rows);
        $this->assertSame('2026-01-01', $rows[0][0]);
        $this->assertSame(3, $rows[0][1]); // total
    }

    public function testBuildExportRowsOverviewType(): void
    {
        $this->repository->method('getOverviewMetrics')->willReturn([
            'totalSearches' => '100',
            'uniqueSessions' => '40',
            'avgResponseTime' => '250',
            'zeroResultSearches' => '10',
            'clickedSearches' => '50',
        ]);

        [$rows, $headers] = $this->service->buildExportRows('overview', 7);

        $this->assertSame(['Metric', 'Value', 'Period'], $headers);
        $this->assertCount(6, $rows);
        $this->assertSame('Total Searches', $rows[0][0]);
        $this->assertSame(100, $rows[0][1]);
    }

    public function testBuildExportRowsDefaultsToTopQueries(): void
    {
        $this->repository->method('getTopQueries')->willReturn([]);

        [, $headers] = $this->service->buildExportRows('unknown-type', 7);

        $this->assertSame(['Query', 'Searches', 'Avg Results', 'Clicks', 'Click Rate (%)'], $headers);
    }
}
