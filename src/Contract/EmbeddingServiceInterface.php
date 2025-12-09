<?php

declare(strict_types=1);

namespace App\Contract;

/**
 * Interface for text embedding generation services.
 * Embeddings are dense vector representations of text used for semantic search.
 */
interface EmbeddingServiceInterface
{
    /**
     * Generate embedding vector for a single text input.
     *
     * @param string $text The text to embed (e.g., query or document content)
     *
     * @throws \RuntimeException If embedding generation fails
     *
     * @return array<float> Dense vector representation (e.g., 768 dimensions for nomic-embed-text)
     */
    public function embed(string $text): array;

    /**
     * Generate embedding vectors for multiple texts in batch.
     * More efficient than calling embed() multiple times.
     *
     * @param array<string> $texts Array of texts to embed
     *
     * @throws \RuntimeException If batch embedding generation fails
     *
     * @return array<array<float>> Array of embedding vectors
     */
    public function embedBatch(array $texts): array;

    /**
     * Get the dimensionality of the embedding vectors.
     *
     * @return int Number of dimensions (e.g., 768 for nomic-embed-text, 1024 for mxbai-embed-large)
     */
    public function getDimensions(): int;
}
