<?php

declare(strict_types=1);

namespace App\Service;

use LanguageDetection\Language;

/**
 * Detects language from text using ngram-based analysis.
 * Single Responsibility: Language detection only.
 */
class LanguageDetector
{
    private Language $detector;

    public function __construct()
    {
        // Focus on most common languages for better accuracy
        $this->detector = new Language(['es', 'en', 'fr', 'pt', 'de', 'it', 'nl', 'pl', 'ru']);
    }

    /**
     * Detects the language of the given text.
     *
     * @return array{language: string, confidence: float, all: array<string, float>}
     */
    public function detect(string $text): array
    {
        // Use first 1000 chars for faster detection
        $sample = substr($text, 0, 1000);

        $results = $this->detector->detect($sample)->bestResults()->close();

        return [
            'language' => array_key_first($results) ?? 'unknown',
            'confidence' => reset($results) ?: 0.0,
            'all' => $results,
        ];
    }
}
