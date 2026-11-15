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
        private readonly int $ollamaMaxTokens,
        private readonly int $ollamaNumThreads,
        private readonly int $ollamaKeepAlive,
        private readonly int $ollamaNumCtx,
    ) {
    }

    /**
     * Translates text to target language using Ollama AI.
     *
     * @throws \Exception if translation fails
     */
    public function translate(string $text, string $sourceLanguage, string $targetLanguage): string
    {
        $sourceLanguageName = $this->languageMapper->getFullName($sourceLanguage);
        $targetLanguageName = $this->languageMapper->getFullName($targetLanguage);
        $prompt = sprintf(
            "You are a professional %s (%s) to %s (%s) translator. "
            . "Your goal is to accurately convey the meaning and nuances of the original %s text "
            . "while adhering to %s grammar, vocabulary, and cultural sensitivities.\n"
            . "Produce only the %s translation, without any additional explanations or commentary. "
            . "Please translate the following %s text into %s:\n\n\n%s",
            $sourceLanguageName,
            $sourceLanguage,
            $targetLanguageName,
            $targetLanguage,
            $sourceLanguageName,
            $targetLanguageName,
            $targetLanguageName,
            $sourceLanguageName,
            $targetLanguageName,
            $text
        );

        $response = $this->httpClient->request('POST', $this->ollamaHost . '/api/generate', [
            'json' => [
                'model' => $this->ollamaModel,
                'prompt' => $prompt,
                'stream' => false,
                'keep_alive' => $this->ollamaKeepAlive,
                'options' => [
                    'temperature' => $this->ollamaTemperature,
                    'num_predict' => $this->ollamaMaxTokens,
                    'num_thread' => $this->ollamaNumThreads,
                    'num_ctx' => $this->ollamaNumCtx,
                ],
            ],
            'timeout' => $this->ollamaTimeout,
        ]);

        $data = $response->toArray();
        if (($data['done'] ?? true) !== true || ($data['done_reason'] ?? null) === 'length') {
            throw new \RuntimeException('Ollama stopped translation before completion.');
        }

        $translatedText = $data['response'] ?? null;
        if (!is_string($translatedText) || trim($translatedText) === '') {
            throw new \RuntimeException('Ollama returned an empty translation.');
        }

        return trim($translatedText);
    }
}
