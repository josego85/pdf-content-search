<?php

namespace App\Controller;

use App\Interface\SearchEngineInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

final class SearchController extends AbstractController
{
    public function __construct(
        private readonly SearchEngineInterface $searchEngine
    ) {}

    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->query->get('q');
            
            if (empty($query)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Query parameter is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            $searchParams = [
                'index' => 'documents',
                'body' => [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['title^2', 'content'],
                            'fuzziness' => 'AUTO'
                        ]
                    ]
                ]
            ];

            $results = $this->searchEngine->search($searchParams);

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'hits' => $results['hits']['hits'] ?? [],
                    'total' => $results['hits']['total']['value'] ?? 0
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Search error occurred',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}