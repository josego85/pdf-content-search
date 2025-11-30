<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PdfPageTranslation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Manages PDF page translations with intelligent caching.
 * Single Responsibility: Translation retrieval/storage coordination.
 * Strategy: Cache → Database → AI Translation (lazy loading).
 */
class TranslationService
{
    private const CACHE_TTL = 604800; // 7 days in seconds

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheItemPoolInterface $cache,
        private readonly OllamaService $ollama,
        private readonly LanguageDetector $languageDetector
    ) {
    }

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
    ): array {
        // Detect source language
        $sourceLanguage = $this->languageDetector->detect($originalText)['language'];

        // If already in target language, return original
        if ($sourceLanguage === $targetLanguage) {
            return [
                'text' => $originalText,
                'source' => 'original',
                'source_language' => $sourceLanguage,
                'cached' => false,
            ];
        }

        // Try cache first (fastest)
        $cacheKey = $this->buildCacheKey($pdfFilename, $pageNumber, $targetLanguage);
        $cachedItem = $this->cache->getItem($cacheKey);

        if ($cachedItem->isHit()) {
            return [
                'text' => $cachedItem->get(),
                'source' => 'cache',
                'source_language' => $sourceLanguage,
                'cached' => true,
            ];
        }

        // Try database (second fastest)
        $translation = $this->findTranslationInDatabase(
            $pdfFilename,
            $pageNumber,
            $targetLanguage
        );

        if ($translation) {
            // Store in cache for next time
            $this->storeInCache($cacheKey, $translation->getTranslatedText());

            return [
                'text' => $translation->getTranslatedText(),
                'source' => 'database',
                'source_language' => $sourceLanguage,
                'cached' => false,
            ];
        }

        // Generate new translation (slowest)
        return $this->translateAndStore(
            $pdfFilename,
            $pageNumber,
            $originalText,
            $sourceLanguage,
            $targetLanguage,
            $cacheKey
        );
    }

    /**
     * Finds existing translation in database.
     */
    private function findTranslationInDatabase(
        string $pdfFilename,
        int $pageNumber,
        string $targetLanguage
    ): ?PdfPageTranslation {
        return $this->entityManager
            ->getRepository(PdfPageTranslation::class)
            ->findOneBy([
                'pdfFilename' => $pdfFilename,
                'pageNumber' => $pageNumber,
                'targetLanguage' => $targetLanguage,
            ]);
    }

    /**
     * Translates text, stores in database and cache.
     */
    private function translateAndStore(
        string $pdfFilename,
        int $pageNumber,
        string $originalText,
        string $sourceLanguage,
        string $targetLanguage,
        string $cacheKey
    ): array {
        // Translate using AI
        $translatedText = $this->ollama->translate($originalText, $targetLanguage);

        // Store in database
        $translation = new PdfPageTranslation();
        $translation->setPdfFilename($pdfFilename);
        $translation->setPageNumber($pageNumber);
        $translation->setSourceLanguage($sourceLanguage);
        $translation->setTargetLanguage($targetLanguage);
        $translation->setOriginalText($originalText);
        $translation->setTranslatedText($translatedText);

        $this->entityManager->persist($translation);
        $this->entityManager->flush();

        // Store in cache
        $this->storeInCache($cacheKey, $translatedText);

        return [
            'text' => $translatedText,
            'source' => 'translated',
            'source_language' => $sourceLanguage,
            'cached' => false,
        ];
    }

    /**
     * Stores translation in cache.
     */
    private function storeInCache(string $cacheKey, string $text): void
    {
        $cachedItem = $this->cache->getItem($cacheKey);
        $cachedItem->set($text);
        $cachedItem->expiresAfter(self::CACHE_TTL);
        $this->cache->save($cachedItem);
    }

    /**
     * Builds consistent cache key.
     */
    private function buildCacheKey(string $pdfFilename, int $pageNumber, string $targetLanguage): string
    {
        return sprintf(
            'pdf_translation_%s_%d_%s',
            md5($pdfFilename),
            $pageNumber,
            $targetLanguage
        );
    }
}
