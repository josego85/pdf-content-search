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
     * Sends multiple batches to the embedding API concurrently.
     * Symfony HttpClient fires all HTTP requests before blocking on any response,
     * so Ollama (with OLLAMA_NUM_PARALLEL ≥ count($batches)) processes them in parallel.
     *
     * @param array<int, string[]> $batches Each element is one batch of texts to embed
     *
     * @throws \RuntimeException If any batch fails
     *
     * @return array<int, float[][]> Per-batch embeddings, indexed same as $batches
     */
    public function embedConcurrentBatches(array $batches): array;

    /**
     * Get the dimensionality of the embedding vectors.
     *
     * @return int Number of dimensions (e.g., 768 for nomic-embed-text, 1024 for mxbai-embed-large)
     */
    public function getDimensions(): int;
}
