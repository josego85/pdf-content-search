<?php

namespace App\Controller;

use App\Contract\SearchEngineInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    public function __construct(
        private readonly SearchEngineInterface $searchEngine
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

            $searchParams = [
                'index' => 'pdf_pages',
                'body' => [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['title^2', 'text'],
                            'fuzziness' => 'AUTO',
                        ],
                    ],
                    'highlight' => [
                        'fields' => [
                            'text' => [
                                'fragment_size' => 150,
                                'number_of_fragments' => 3,
                                'pre_tags' => ['<mark>'],
                                'post_tags' => ['</mark>'],
                            ],
                        ],
                    ],
                ],
            ];

            $results = $this->searchEngine->search($searchParams);

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'hits' => $results['hits']['hits'] ?? [],
                    'total' => $results['hits']['total']['value'] ?? 0,
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
