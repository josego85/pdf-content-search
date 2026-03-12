<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\LanguageDetectorInterface;
use LanguageDetection\Language;

/**
 * Detects language from text using ngram-based analysis.
 * Single Responsibility: Language detection only.
 */
final readonly class LanguageDetector implements LanguageDetectorInterface
{
    private Language $detector;

    /**
     * @param array<int, string> $supportedLanguages ISO 639-1 codes, e.g. ['es', 'en', 'de']
     */
    public function __construct(array $supportedLanguages)
    {
        // Aligned with frontend supported languages (assets/constants/languages.js)
        $this->detector = new Language($supportedLanguages);
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
