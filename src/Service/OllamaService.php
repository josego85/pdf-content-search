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
    private const DEFAULT_MODEL = 'llama3.2:1b';
    private const DEFAULT_TIMEOUT = 60;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $ollamaHost = 'http://ollama:11434'
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
            'Translate the following text to %s. Respond ONLY with the translation, no explanations:\n\n%s',
            $this->getLanguageName($targetLanguage),
            $text
        );

        $response = $this->httpClient->request('POST', $this->ollamaHost . '/api/generate', [
            'json' => [
                'model' => self::DEFAULT_MODEL,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.3,
                ],
            ],
            'timeout' => self::DEFAULT_TIMEOUT,
        ]);

        $data = $response->toArray();

        return trim($data['response'] ?? '');
    }

    /**
     * Maps ISO 639-1 language codes to full names for better AI understanding.
     */
    private function getLanguageName(string $code): string
    {
        return match ($code) {
            'es' => 'Spanish',
            'en' => 'English',
            'fr' => 'French',
            'pt' => 'Portuguese',
            'de' => 'German',
            'it' => 'Italian',
            'nl' => 'Dutch',
            'pl' => 'Polish',
            'ru' => 'Russian',
            default => $code,
        };
    }
}
