<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\EmbeddingServiceInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Ollama-based text embedding service.
 * Generates dense vector representations using Ollama's embedding API.
 */
final readonly class OllamaEmbeddingService implements EmbeddingServiceInterface
{
    private const int MAX_RETRIES = 3;
    private const int RETRY_DELAY_MS = 500;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $ollamaHost,
        private string $embeddingModel,
        private int $dimensions = 768,
        private int $keepAlive = -1,
    ) {
    }

    public function embed(string $text): array
    {
        // Send input as a plain string — matches the original Ollama /api/embed contract.
        // Sending a single-element array causes 400 on some Ollama versions.
        return $this->requestSingle($text);
    }

    public function embedConcurrentBatches(array $batches): array
    {
        if ([] === $batches) {
            return [];
        }

        // Fire all HTTP requests before blocking on any response.
        // Symfony HttpClient opens all connections simultaneously via curl_multi.
        // Ollama processes them in parallel when OLLAMA_NUM_PARALLEL >= count($batches).
        $responses = [];
        foreach ($batches as $i => $texts) {
            if ([] === $texts) {
                continue;
            }

            $responses[$i] = $this->httpClient->request('POST', "{$this->ollamaHost}/api/embed", [
                'json' => [
                    'model' => $this->embeddingModel,
                    'input' => $texts,
                    'keep_alive' => $this->keepAlive,
                ],
                'timeout' => 30 * count($texts),
            ]);
        }

        // Collect responses — they have been executing in parallel on the Ollama side.
        $results = [];
        foreach ($responses as $i => $response) {
            $data = $response->toArray();

            if (!isset($data['embeddings']) || !is_array($data['embeddings']) || [] === $data['embeddings']) {
                throw new \RuntimeException(sprintf('Concurrent batch %d: invalid response from Ollama', $i));
            }

            foreach ($data['embeddings'] as $idx => $embedding) {
                if (count($embedding) !== $this->dimensions) {
                    throw new \RuntimeException(sprintf('Concurrent batch %d: expected %d dimensions at index %d, got %d', $i, $this->dimensions, $idx, count($embedding)));
                }
            }

            $results[$i] = $data['embeddings'];
        }

        return $results;
    }

    public function getDimensions(): int
    {
        return $this->dimensions;
    }

    /**
     * Single-text embedding — input sent as a plain string.
     *
     * @return float[]
     */
    private function requestSingle(string $input): array
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = $this->httpClient->request('POST', "{$this->ollamaHost}/api/embed", [
                    'json' => [
                        'model' => $this->embeddingModel,
                        'input' => $input,
                        'keep_alive' => $this->keepAlive,
                    ],
                    'timeout' => 30,
                ]);

                $data = $response->toArray();

                if (!isset($data['embeddings']) || !is_array($data['embeddings']) || [] === $data['embeddings']) {
                    throw new \RuntimeException('Invalid response from Ollama: missing or invalid embeddings field');
                }

                $embedding = $data['embeddings'][0];

                if (count($embedding) !== $this->dimensions) {
                    throw new \RuntimeException(sprintf('Expected %d dimensions, got %d', $this->dimensions, count($embedding)));
                }

                return $embedding;
            } catch (\RuntimeException $e) {
                // Permanent failure — do not retry.
                throw $e;
            } catch (\Throwable $e) {
                // Transient failure (network, timeout) — retry with backoff.
                $lastException = $e;
                ++$attempt;

                if ($attempt < self::MAX_RETRIES) {
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
                }
            }
        }

        throw new \RuntimeException(sprintf('Failed to generate embedding after %d attempts: %s', self::MAX_RETRIES, $lastException->getMessage()), 0, $lastException);
    }
}
