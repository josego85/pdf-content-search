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
     * Get the name of the vector field in the underlying store.
     *
     * @return string Field name (e.g., 'text_embedding', 'vector', 'embedding')
     */
    public function getVectorFieldName(): string;
}
