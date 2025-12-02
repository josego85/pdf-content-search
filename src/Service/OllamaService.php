<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Handles AI-powered translation using Ollama.
 * Single Responsibility: Ollama API communication only.
 */
class OllamaService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LanguageMapper $languageMapper,
        private readonly string $ollamaHost,
        private readonly string $ollamaModel,
        private readonly int $ollamaTimeout,
        private readonly float $ollamaTemperature,
        private readonly int $ollamaMaxTokens
    ) {
    }

    /**
     * Translates text to target language using Ollama AI.
     *
     * @throws \Exception if translation fails
     */
    public function translate(string $text, string $targetLanguage): string
    {
        $prompt = sprintf(
            "Translate the ENTIRE following text to %s. Translate ALL paragraphs and ALL sentences. Do NOT summarize. Respond ONLY with the complete translation:\n\n%s",
            $this->languageMapper->getFullName($targetLanguage),
            $text
        );

        $response = $this->httpClient->request('POST', $this->ollamaHost . '/api/generate', [
            'json' => [
                'model' => $this->ollamaModel,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => $this->ollamaTemperature,
                    'num_predict' => $this->ollamaMaxTokens,
                ],
            ],
            'timeout' => $this->ollamaTimeout,
        ]);

        $data = $response->toArray();

        return trim($data['response'] ?? '');
    }
}
