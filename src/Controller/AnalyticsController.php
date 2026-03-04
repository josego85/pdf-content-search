<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SearchAnalyticsRepository;
use App\Service\AnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'api_analytics_')]
final class AnalyticsController extends AbstractController
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
        private readonly SearchAnalyticsRepository $analyticsRepository
    ) {
    }

    #[Route('/api/analytics/overview', name: 'api_analytics_overview', methods: ['GET'])]
    public function overview(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);

        return new JsonResponse([
            'status' => 'success',
            'data' => $this->analyticsService->getOverview($days),
        ]);
    }

    #[Route('/api/analytics/top-queries', name: 'api_analytics_top_queries', methods: ['GET'])]
    public function topQueries(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $limit = (int) $request->query->get('limit', 20);

        return new JsonResponse([
            'status' => 'success',
            'data' => $this->analyticsService->getTopQueries($days, $limit),
        ]);
    }

    #[Route('/api/analytics/trends', name: 'api_analytics_trends', methods: ['GET'])]
    public function trends(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);

        return new JsonResponse([
            'status' => 'success',
            'data' => $this->analyticsService->getTrends($days),
        ]);
    }

    #[Route('/api/analytics/click-positions', name: 'api_analytics_click_positions', methods: ['GET'])]
    public function clickPositions(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);

        return new JsonResponse([
            'status' => 'success',
            'data' => $this->analyticsService->getClickPositions($days),
        ]);
    }

    #[Route('/api/analytics/zero-results', name: 'api_analytics_zero_results', methods: ['GET'])]
    public function zeroResults(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $limit = (int) $request->query->get('limit', 20);
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        return new JsonResponse([
            'status' => 'success',
            'data' => $this->analyticsRepository->getZeroResultQueries($startDate, $endDate, $limit),
        ]);
    }

    #[Route('/api/analytics/export', name: 'api_analytics_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $days = (int) $request->query->get('days', 7);
        $type = $request->query->get('type', 'top-queries');
        $format = $request->query->get('format', 'csv');

        [$rows, $headers] = $this->analyticsService->buildExportRows($type, $days);

        $filename = sprintf('analytics-%s-%dd-%s.%s', $type, $days, date('Y-m-d'), $format);

        if ('json' === $format) {
            $namedRows = array_map(
                static fn (array $row): array => array_combine($headers, $row),
                $rows
            );

            return new Response(
                (string) json_encode($namedRows, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]
            );
        }

        return new StreamedResponse(static function () use ($rows, $headers): void {
            $output = fopen('php://output', 'w');
            assert($output !== false);
            fputcsv($output, $headers, escape: '\\');
            foreach ($rows as $row) {
                fputcsv($output, $row, escape: '\\');
            }
            fclose($output);
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/api/analytics/track-click', name: 'api_analytics_track_click', methods: ['POST'])]
    public function trackClick(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $sessionId = $request->getSession()->getId();
        $query = $data['query'] ?? '';
        $position = (int) ($data['position'] ?? 0);
        $pdfPath = $data['pdf_path'] ?? null;
        $page = isset($data['page']) ? (int) $data['page'] : null;

        $analytics = $this->analyticsRepository->findOneBy(
            ['sessionId' => $sessionId, 'query' => $query, 'clicked' => false],
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

            return new JsonResponse(['status' => 'success', 'message' => 'Click tracked']);
        }

        return new JsonResponse(
            ['status' => 'error', 'message' => 'Search not found'],
            Response::HTTP_NOT_FOUND
        );
    }
}
