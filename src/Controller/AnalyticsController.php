<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SearchAnalyticsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/analytics', name: 'api_analytics_')]
final class AnalyticsController extends AbstractController
{
    public function __construct(
        private readonly SearchAnalyticsRepository $analyticsRepository
    ) {
    }

    #[Route('/overview', name: 'overview', methods: ['GET'])]
    public function overview(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        $metrics = $this->analyticsRepository->getOverviewMetrics($startDate, $endDate);

        // Calculate derived metrics
        $totalSearches = (int) $metrics['totalSearches'];
        $clickedSearches = (int) $metrics['clickedSearches'];
        $zeroResultSearches = (int) $metrics['zeroResultSearches'];

        $clickThroughRate = $totalSearches > 0
            ? round(($clickedSearches / $totalSearches) * 100, 2)
            : 0;

        $successRate = $totalSearches > 0
            ? round((($totalSearches - $zeroResultSearches) / $totalSearches) * 100, 2)
            : 0;

        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'total_searches' => $totalSearches,
                'unique_sessions' => (int) $metrics['uniqueSessions'],
                'avg_response_time_ms' => round((float) $metrics['avgResponseTime']),
                'zero_result_searches' => $zeroResultSearches,
                'click_through_rate' => $clickThroughRate,
                'success_rate' => $successRate,
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'days' => $days,
                ],
            ],
        ]);
    }

    #[Route('/top-queries', name: 'top_queries', methods: ['GET'])]
    public function topQueries(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $limit = (int) $request->query->get('limit', 20);

        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        $queries = $this->analyticsRepository->getTopQueries($startDate, $endDate, $limit);

        // Format results
        $formatted = array_map(static function ($item) {
            $searchCount = (int) $item['searchCount'];
            $clicks = (int) $item['clicks'];

            return [
                'query' => $item['query'],
                'search_count' => $searchCount,
                'avg_results' => round((float) $item['avgResults'], 1),
                'clicks' => $clicks,
                'click_rate' => $searchCount > 0
                    ? round(($clicks / $searchCount) * 100, 1)
                    : 0,
            ];
        }, $queries);

        return new JsonResponse([
            'status' => 'success',
            'data' => $formatted,
        ]);
    }

    #[Route('/trends', name: 'trends', methods: ['GET'])]
    public function trends(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        $trends = $this->analyticsRepository->getSearchTrends($startDate, $endDate);

        // Group by date and strategy
        $grouped = [];
        foreach ($trends as $item) {
            $date = $item['date'];

            if ($date instanceof \DateTimeInterface) {
                $date = $date->format('Y-m-d');
            }

            $strategy = $item['searchStrategy'];

            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'total' => 0,
                    'by_strategy' => [],
                ];
            }

            $grouped[$date]['by_strategy'][$strategy] = (int) $item['searchCount'];
            $grouped[$date]['total'] += (int) $item['searchCount'];
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => array_values($grouped),
        ]);
    }

    #[Route('/click-positions', name: 'click_positions', methods: ['GET'])]
    public function clickPositions(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        $positions = $this->analyticsRepository->getClickPositionDistribution($startDate, $endDate);

        $formatted = array_map(static function ($item) {
            return [
                'position' => (int) $item['position'],
                'clicks' => (int) $item['clicks'],
            ];
        }, $positions);

        return new JsonResponse([
            'status' => 'success',
            'data' => $formatted,
        ]);
    }

    #[Route('/zero-results', name: 'zero_results', methods: ['GET'])]
    public function zeroResults(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $limit = (int) $request->query->get('limit', 20);

        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        $queries = $this->analyticsRepository->getZeroResultQueries($startDate, $endDate, $limit);

        return new JsonResponse([
            'status' => 'success',
            'data' => $queries,
        ]);
    }

    #[Route('/track-click', name: 'track_click', methods: ['POST'])]
    public function trackClick(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $sessionId = $request->getSession()->getId();
        $query = $data['query'] ?? '';
        $position = (int) ($data['position'] ?? 0);
        $pdfPath = $data['pdf_path'] ?? null;
        $page = isset($data['page']) ? (int) $data['page'] : null;

        // Find the most recent search for this session and query
        $analytics = $this->analyticsRepository->findOneBy(
            [
                'sessionId' => $sessionId,
                'query' => $query,
                'clicked' => false,
            ],
            ['createdAt' => 'DESC']
        );

        if ($analytics instanceof \App\Entity\SearchAnalytics) {
            $analytics->setClicked(true);
            $analytics->setClickedPosition($position);
            $analytics->setClickedPdf($pdfPath);
            $analytics->setClickedPage($page);

            $timeToClick = (int) (microtime(true) * 1000 - $analytics->getCreatedAt()->getTimestamp() * 1000);
            $analytics->setTimeToClickMs($timeToClick);

            $this->analyticsRepository->save($analytics);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Click tracked',
            ]);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'Search not found',
        ], 404);
    }
}
