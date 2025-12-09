<?php

declare(strict_types=1);

namespace App\Contract;

/**
 * Vector store abstraction for semantic search.
 * Allows switching between different vector databases (Elasticsearch, Pinecone, Weaviate, Qdrant, etc.)
 * without changing application logic.
 *
 * Single Responsibility: Vector similarity search operations.
 * Open/Closed: Open for extension (new vector DB implementations), closed for modification.
 */
interface VectorStoreInterface
{
    /**
     * Search documents by vector similarity.
     *
     * @param array<float> $vector Query vector for similarity search
     * @param int $k Number of nearest neighbors to return
     * @param int $numCandidates Number of candidates for ANN (approximate nearest neighbor) search
     * @param array<string> $sourceFields Fields to include in response (e.g., ['title', 'page', 'text'])
     *
     * @return array<string, mixed> Search results with hits
     */
    public function searchByVector(
        array $vector,
        int $k = 50,
        int $numCandidates = 100,
        array $sourceFields = []
    ): array;

    /**
     * Index a document with its vector representation.
     *
     * @param string $id Document ID
     * @param array<string, mixed> $document Document fields (title, text, etc.)
     * @param array<float> $vector Vector embedding for semantic search
     */
    public function indexWithVector(string $id, array $document, array $vector): void;

    /**
     * Get the name of the vector field in the underlying store.
     * Useful for debugging and query building.
     *
     * @return string Field name (e.g., 'text_embedding', 'vector', 'embedding')
     */
    public function getVectorFieldName(): string;

    /**
     * Get expected vector dimensions.
     * Useful for validation and debugging.
     *
     * @return int Vector dimensions (e.g., 768, 1024, 1536)
     */
    public function getVectorDimensions(): int;
}
