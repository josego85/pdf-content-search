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
        private int $dimensions = 768
    ) {
    }

    public function embed(string $text): array
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = $this->httpClient->request('POST', "{$this->ollamaHost}/api/embed", [
                    'json' => [
                        'model' => $this->embeddingModel,
                        'input' => $text,
                    ],
                    'timeout' => 30,
                ]);

                $data = $response->toArray();

                if (!isset($data['embeddings']) || !is_array($data['embeddings']) || empty($data['embeddings'])) {
                    throw new \RuntimeException('Invalid response from Ollama: missing or invalid embeddings field');
                }

                // API returns array of embeddings, we only sent one input, so take the first
                $embedding = $data['embeddings'][0];

                // Validate embedding dimensions
                if (count($embedding) !== $this->dimensions) {
                    throw new \RuntimeException(sprintf('Expected %d dimensions, got %d', $this->dimensions, count($embedding)));
                }

                return $embedding;
            } catch (\Throwable $e) {
                $lastException = $e;
                ++$attempt;

                if ($attempt < self::MAX_RETRIES) {
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempt); // Exponential backoff
                }
            }
        }

        throw new \RuntimeException(sprintf('Failed to generate embedding after %d attempts: %s', self::MAX_RETRIES, $lastException?->getMessage()), 0, $lastException);
    }

    public function embedBatch(array $texts): array
    {
        $embeddings = [];

        foreach ($texts as $text) {
            $embeddings[] = $this->embed($text);
        }

        return $embeddings;
    }

    public function getDimensions(): int
    {
        return $this->dimensions;
    }
}
