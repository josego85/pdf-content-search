<?php

declare(strict_types=1);

namespace App\Contract;

interface TranslationServiceInterface
{
    /**
     * Checks if translation exists in cache or database WITHOUT generating new translation.
     * Returns null if translation needs to be generated.
     *
     * @return array{text: string, source: string, source_language: string, cached: bool}|null
     */
    public function findExistingTranslation(
        string $pdfFilename,
        int $pageNumber,
        string $originalText,
        string $targetLanguage
    ): ?array;

    /**
     * Gets translation from cache, database, or generates new one.
     *
     * @return array{text: string, source: string, source_language: string, cached: bool}
     */
    public function getTranslation(
        string $pdfFilename,
        int $pageNumber,
        string $originalText,
        string $targetLanguage
    ): array;
}
