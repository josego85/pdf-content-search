<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Maps ISO 639-1 language codes to full names.
 * Single Responsibility: Language code mapping.
 *
 * Centralized service to avoid duplication across the application.
 * Synchronizes with frontend languages.js constants.
 *
 * Single Source of Truth: Add/remove languages in LANGUAGES constant only.
 */
final class LanguageMapper
{
    /**
     * Language definitions (single source of truth).
     * Synchronize with assets/constants/languages.js when adding/removing languages.
     */
    private const array LANGUAGES = [
        'es' => ['label' => 'ES', 'fullName' => 'Spanish'],
        'en' => ['label' => 'EN', 'fullName' => 'English'],
        'fr' => ['label' => 'FR', 'fullName' => 'French'],
        'pt' => ['label' => 'PT', 'fullName' => 'Portuguese'],
        'de' => ['label' => 'DE', 'fullName' => 'German'],
        'it' => ['label' => 'IT', 'fullName' => 'Italian'],
        'nl' => ['label' => 'NL', 'fullName' => 'Dutch'],
        'pl' => ['label' => 'PL', 'fullName' => 'Polish'],
        'ru' => ['label' => 'RU', 'fullName' => 'Russian'],
    ];

    /**
     * Get full language name for AI prompts (English names for better AI understanding).
     */
    public function getFullName(string $code): string
    {
        return self::LANGUAGES[$code]['fullName'] ?? $code;
    }

    /**
     * Get display label (matches frontend LANGUAGES constant).
     */
    public function getLabel(string $code): string
    {
        return self::LANGUAGES[$code]['label'] ?? strtoupper($code);
    }

    /**
     * Check if language code is supported.
     */
    public function isSupported(string $code): bool
    {
        return isset(self::LANGUAGES[$code]);
    }

    /**
     * Get all supported language codes.
     *
     * @return array<string>
     */
    public function getSupportedCodes(): array
    {
        return array_keys(self::LANGUAGES);
    }

    /**
     * Get all languages with their metadata.
     *
     * @return array<string, array{label: string, fullName: string}>
     */
    public function getAllLanguages(): array
    {
        return self::LANGUAGES;
    }
}
