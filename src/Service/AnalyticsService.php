<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\SearchAnalyticsRepository;

final readonly class AnalyticsService
{
    public function __construct(
        private SearchAnalyticsRepository $repository,
    ) {
    }

    /**
     * @return array{
     *     total_searches: int,
     *     unique_sessions: int,
     *     avg_response_time_ms: float,
     *     zero_result_searches: int,
     *     click_through_rate: float|int,
     *     success_rate: float|int,
     *     period: array{start: string, end: string, days: int}
     * }
     */
    public function getOverview(int $days): array
    {
        [$startDate, $endDate] = $this->dateRange($days);
        $metrics = $this->repository->getOverviewMetrics($startDate, $endDate);

        $totalSearches = (int) $metrics['totalSearches'];
        $clickedSearches = (int) $metrics['clickedSearches'];
        $zeroResultSearches = (int) $metrics['zeroResultSearches'];

        return [
            'total_searches' => $totalSearches,
            'unique_sessions' => (int) $metrics['uniqueSessions'],
            'avg_response_time_ms' => round((float) $metrics['avgResponseTime']),
            'zero_result_searches' => $zeroResultSearches,
            'click_through_rate' => $this->rate($clickedSearches, $totalSearches),
            'success_rate' => $this->rate($totalSearches - $zeroResultSearches, $totalSearches),
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $days,
            ],
        ];
    }

    /** @return list<array{query: string, search_count: int, avg_results: float, clicks: int, click_rate: float|int}> */
    public function getTopQueries(int $days, int $limit): array
    {
        [$startDate, $endDate] = $this->dateRange($days);
        $queries = $this->repository->getTopQueries($startDate, $endDate, $limit);

        return array_map(static function (array $item): array {
            $searchCount = (int) $item['searchCount'];
            $clicks = (int) $item['clicks'];

            return [
                'query' => $item['query'],
                'search_count' => $searchCount,
                'avg_results' => round((float) $item['avgResults'], 1),
                'clicks' => $clicks,
                'click_rate' => $searchCount > 0 ? round(($clicks / $searchCount) * 100, 1) : 0,
            ];
        }, $queries);
    }

    /** @return list<array{date: string, total: int, by_strategy: array<string, int>}> */
    public function getTrends(int $days): array
    {
        [$startDate, $endDate] = $this->dateRange($days);
        $trends = $this->repository->getSearchTrends($startDate, $endDate);

        $grouped = [];
        foreach ($trends as $item) {
            $date = $item['date'] instanceof \DateTimeInterface
                ? $item['date']->format('Y-m-d')
                : $item['date'];

            if (!isset($grouped[$date])) {
                $grouped[$date] = ['date' => $date, 'total' => 0, 'by_strategy' => []];
            }

            $grouped[$date]['by_strategy'][$item['searchStrategy']] = (int) $item['searchCount'];
            $grouped[$date]['total'] += (int) $item['searchCount'];
        }

        return array_values($grouped);
    }

    /**
     * @return list<array{position: int, clicks: int, impressions: int, ctr: float}>
     */
    public function getClickPositions(int $days, int $maxPosition = 20): array
    {
        [$startDate, $endDate] = $this->dateRange($days);

        $rawClicks = $this->repository->getClickPositionDistribution($startDate, $endDate);
        $resultCounts = $this->repository->getResultCountsInRange($startDate, $endDate);

        $clicksByPosition = [];
        foreach ($rawClicks as $row) {
            $clicksByPosition[(int) $row['position']] = (int) $row['clicks'];
        }

        // Impressions at position P = searches where displayed results >= P.
        // displayedResultsCount is already capped to page size at collection time.
        $impressions = array_fill(1, $maxPosition, 0);
        foreach ($resultCounts as $count) {
            $cap = min((int) $count, $maxPosition);
            for ($pos = 1; $pos <= $cap; ++$pos) {
                ++$impressions[$pos];
            }
        }

        $result = [];
        for ($pos = 1; $pos <= $maxPosition; ++$pos) {
            $imp = $impressions[$pos];

            if ($imp === 0) {
                continue;
            }
            $clicks = $clicksByPosition[$pos] ?? 0;
            $result[] = [
                'position' => $pos,
                'clicks' => $clicks,
                'impressions' => $imp,
                'ctr' => round(($clicks / $imp) * 100, 1),
            ];
        }

        return $result;
    }

    /** @return array{array<array<int|float|string>>, string[]} */
    public function buildExportRows(string $type, int $days): array
    {
        [$startDate, $endDate] = $this->dateRange($days);

        return match ($type) {
            'overview' => $this->overviewRows($startDate, $endDate, $days),
            'trends' => $this->trendsRows($startDate, $endDate),
            default => $this->topQueriesRows($startDate, $endDate),
        };
    }

    /** @return array{\DateTime, \DateTime} */
    private function dateRange(int $days): array
    {
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        return [$startDate, $endDate];
    }

    private function rate(int $part, int $total, int $decimals = 2): float|int
    {
        return $total > 0 ? round(($part / $total) * 100, $decimals) : 0;
    }

    /** @return array{array<array<int|float|string>>, string[]} */
    private function topQueriesRows(\DateTime $startDate, \DateTime $endDate): array
    {
        $headers = ['Query', 'Searches', 'Avg Results', 'Clicks', 'Click Rate (%)'];
        $queries = $this->repository->getTopQueries($startDate, $endDate, 500);

        $rows = array_map(static function (array $item): array {
            $searchCount = (int) $item['searchCount'];
            $clicks = (int) $item['clicks'];

            return [
                $item['query'],
                $searchCount,
                round((float) $item['avgResults'], 1),
                $clicks,
                $searchCount > 0 ? round(($clicks / $searchCount) * 100, 1) : 0,
            ];
        }, $queries);

        return [$rows, $headers];
    }

    /** @return array{array<array<int|string>>, string[]} */
    private function trendsRows(\DateTime $startDate, \DateTime $endDate): array
    {
        $headers = ['Date', 'Total', 'Hybrid AI', 'Exact', 'Prefix'];
        $trends = $this->repository->getSearchTrends($startDate, $endDate);

        $grouped = [];
        foreach ($trends as $item) {
            $date = $item['date'] instanceof \DateTimeInterface
                ? $item['date']->format('Y-m-d')
                : $item['date'];

            if (!isset($grouped[$date])) {
                $grouped[$date] = ['date' => $date, 'total' => 0, 'hybrid_ai' => 0, 'exact' => 0, 'prefix' => 0];
            }

            $grouped[$date][$item['searchStrategy']] = (int) $item['searchCount'];
            $grouped[$date]['total'] += (int) $item['searchCount'];
        }

        $rows = array_map(
            static fn (array $d): array => [$d['date'], $d['total'], $d['hybrid_ai'], $d['exact'], $d['prefix']],
            array_values($grouped)
        );

        return [$rows, $headers];
    }

    /** @return array{array<array<int|float|string>>, string[]} */
    private function overviewRows(\DateTime $startDate, \DateTime $endDate, int $days): array
    {
        $headers = ['Metric', 'Value', 'Period'];
        $metrics = $this->repository->getOverviewMetrics($startDate, $endDate);

        $totalSearches = (int) $metrics['totalSearches'];
        $clickedSearches = (int) $metrics['clickedSearches'];
        $zeroResultSearches = (int) $metrics['zeroResultSearches'];
        $period = sprintf('%s — %s (%d days)', $startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $days);

        $rows = [
            ['Total Searches', $totalSearches, $period],
            ['Unique Sessions', (int) $metrics['uniqueSessions'], $period],
            ['Avg Response Time (ms)', (int) round((float) $metrics['avgResponseTime']), $period],
            ['Zero Result Searches', $zeroResultSearches, $period],
            ['Click-Through Rate (%)', $this->rate($clickedSearches, $totalSearches), $period],
            ['Success Rate (%)', $this->rate($totalSearches - $zeroResultSearches, $totalSearches), $period],
        ];

        return [$rows, $headers];
    }
}
