<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\VectorStoreInterface;
use Elastic\Elasticsearch\Client;

/**
 * Elasticsearch implementation of vector store using kNN search.
 * Supports dense_vector fields with HNSW (Hierarchical Navigable Small World) algorithm.
 *
 * Requirements: Elasticsearch 8.0+ with dense_vector field support.
 */
final readonly class ElasticsearchVectorStore implements VectorStoreInterface
{
    private const string VECTOR_FIELD = 'text_embedding';

    public function __construct(
        private Client $client,
        private string $indexName,
        private int $dimensions = 768
    ) {
    }

    public function searchByVector(
        array $vector,
        int $k = 50,
        int $numCandidates = 100,
        array $sourceFields = []
    ): array {
        // Validate vector dimensions
        if (count($vector) !== $this->dimensions) {
            throw new \InvalidArgumentException(sprintf('Vector must have %d dimensions, got %d', $this->dimensions, count($vector)));
        }

        $params = [
            'index' => $this->indexName,
            'body' => [
                'knn' => [
                    'field' => self::VECTOR_FIELD,
                    'query_vector' => $vector,
                    'k' => $k,
                    'num_candidates' => $numCandidates,
                ],
            ],
        ];

        // Include source fields if specified
        if (!empty($sourceFields)) {
            $params['body']['_source'] = $sourceFields;
        }

        return $this->client->search($params)->asArray();
    }

    public function indexWithVector(string $id, array $document, array $vector): void
    {
        // Validate vector dimensions
        if (count($vector) !== $this->dimensions) {
            throw new \InvalidArgumentException(sprintf('Vector must have %d dimensions, got %d', $this->dimensions, count($vector)));
        }

        $params = [
            'index' => $this->indexName,
            'id' => $id,
            'body' => array_merge($document, [
                self::VECTOR_FIELD => $vector,
            ]),
        ];

        $this->client->index($params);
    }

    public function getVectorFieldName(): string
    {
        return self::VECTOR_FIELD;
    }

    public function getVectorDimensions(): int
    {
        return $this->dimensions;
    }
}
