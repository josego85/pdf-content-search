<?php

declare(strict_types=1);

namespace App\Controller;

use App\Contract\QueryBuilderInterface;
use App\Contract\RankFusionServiceInterface;
use App\Contract\SearchEngineInterface;
use App\Search\SearchStrategy;
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
        private readonly RankFusionServiceInterface $rankFusion
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

            // Get strategy from request (defaults to HYBRID_AI for best results)
            $strategyParam = $request->query->get('strategy', 'hybrid_ai');
            $strategy = SearchStrategy::tryFrom($strategyParam) ?? SearchStrategy::HYBRID_AI;

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
            $results = $this->searchEngine->search($searchParams);

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'hits' => $results['hits']['hits'] ?? [],
                    'total' => $results['hits']['total']['value'] ?? 0,
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
}
