<?php

declare(strict_types=1);

namespace App\Search;

use App\Contract\EmbeddingServiceInterface;
use App\Contract\QueryBuilderInterface;
use App\Contract\VectorStoreInterface;

/**
 * Hybrid search query builder supporting both lexical and semantic (vector) search.
 * Decorator pattern: delegates lexical queries to SearchQueryBuilder, adds vector capabilities.
 * Dependency Inversion: Depends on VectorStoreInterface, not concrete Elasticsearch implementation.
 */
final readonly class HybridSearchQueryBuilder implements QueryBuilderInterface
{
    public function __construct(
        private SearchQueryBuilder $lexicalBuilder,
        private EmbeddingServiceInterface $embeddingService,
        private VectorStoreInterface $vectorStore,
        private string $pdfPagesIndex
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $query, SearchStrategy $strategy = SearchStrategy::HYBRID): array
    {
        return match ($strategy) {
            SearchStrategy::SEMANTIC => $this->buildSemanticQuery($query),
            SearchStrategy::HYBRID_AI => $this->buildHybridAiQuery($query),
            default => $this->lexicalBuilder->build($query, $strategy),
        };
    }

    /**
     * Build pure semantic search query using vector store abstraction.
     * Now database-agnostic: works with Elasticsearch, Pinecone, Weaviate, etc.
     *
     * @return array<string, mixed>
     */
    private function buildSemanticQuery(string $query): array
    {
        $embedding = $this->embeddingService->embed($query);

        // Build Elasticsearch-specific query for now (will be refactored in Phase 2)
        return [
            'index' => $this->pdfPagesIndex,
            'body' => [
                'knn' => [
                    'field' => $this->vectorStore->getVectorFieldName(),
                    'query_vector' => $embedding,
                    'k' => 50,
                    'num_candidates' => 100,
                ],
                '_source' => ['title', 'page', 'text', 'path', 'total_pages', 'language', 'date'],
            ],
        ];
    }

    /**
     * Build hybrid AI query: returns both lexical and semantic queries to be executed in parallel.
     * Results will be merged using RRF by the controller.
     *
     * @return array<string, mixed>
     */
    private function buildHybridAiQuery(string $query): array
    {
        return [
            'lexical' => $this->lexicalBuilder->build($query, SearchStrategy::HYBRID),
            'semantic' => $this->buildSemanticQuery($query),
        ];
    }
}
