<?php

declare(strict_types=1);

namespace App\Controller;

use App\Contract\QueryBuilderInterface;
use App\Contract\RankFusionServiceInterface;
use App\Contract\SearchEngineInterface;
use App\Search\SearchStrategy;
use App\Service\AnalyticsCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Search controller.
 * Single Responsibility: Handle HTTP requests for search operations.
 * Dependency Inversion: Depends on QueryBuilderInterface, not concrete implementation.
 */
final class SearchController extends AbstractController
{
    public function __construct(
        private readonly SearchEngineInterface $searchEngine,
        private readonly QueryBuilderInterface $queryBuilder,
        private readonly RankFusionServiceInterface $rankFusion,
        private readonly AnalyticsCollector $analyticsCollector
    ) {
    }

    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->query->get('q');

            if (empty($query)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Search query cannot be empty.',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Auto-detect search strategy based on query pattern
            $strategyParam = $request->query->get('strategy', 'hybrid_ai');
            $strategy = $this->detectSearchStrategy($query, $strategyParam);

            // Build query using builder pattern
            $searchParams = $this->queryBuilder->build($query, $strategy);

            // Handle hybrid AI search: execute parallel queries and merge with RRF
            if ($strategy === SearchStrategy::HYBRID_AI && isset($searchParams['lexical'], $searchParams['semantic'])) {
                $startTime = microtime(true);

                // Execute both searches
                $lexicalResults = $this->searchEngine->search($searchParams['lexical']);
                $semanticResults = $this->searchEngine->search($searchParams['semantic']);

                $lexicalHits = $lexicalResults['hits']['hits'] ?? [];
                $semanticHits = $semanticResults['hits']['hits'] ?? [];

                // Merge results using RRF (equal weights: 0.5 lexical, 0.5 semantic)
                $mergedHits = $this->rankFusion->merge([$lexicalHits, $semanticHits], [0.5, 0.5]);

                $duration = (int) ((microtime(true) - $startTime) * 1000);

                // Log analytics asynchronously
                $this->analyticsCollector->logSearch(
                    $request,
                    $query,
                    'hybrid_ai',
                    count($mergedHits),
                    $duration
                );

                return new JsonResponse([
                    'status' => 'success',
                    'data' => [
                        'hits' => $mergedHits,
                        'total' => count($mergedHits),
                        'strategy' => 'hybrid_ai',
                        'duration_ms' => $duration,
                    ],
                ]);
            }

            // Standard search (lexical, semantic, exact, prefix)
            $startTime = microtime(true);
            $results = $this->searchEngine->search($searchParams);
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            $resultsCount = $results['hits']['total']['value'] ?? 0;

            // Log analytics asynchronously
            $this->analyticsCollector->logSearch(
                $request,
                $query,
                $strategy->value,
                $resultsCount,
                $duration
            );

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'hits' => $results['hits']['hits'] ?? [],
                    'total' => $resultsCount,
                    'strategy' => $strategy->value,
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Search error occurred',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Auto-detect the best search strategy based on query pattern.
     * Smart defaults following UX best practices (invisible intelligence).
     */
    private function detectSearchStrategy(string $query, string $requestedStrategy): SearchStrategy
    {
        // If user requested a specific strategy via API, respect it
        $explicitStrategy = SearchStrategy::tryFrom($requestedStrategy);

        // Auto-detection: queries with quotes should use exact match
        if (preg_match('/^["\'].*["\']$/', trim($query))) {
            return SearchStrategy::EXACT;
        }

        // Use requested strategy or default to HYBRID_AI
        return $explicitStrategy ?? SearchStrategy::HYBRID_AI;
    }
}
