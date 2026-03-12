<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\LanguageDetectorInterface;
use App\Contract\TranslationServiceInterface;
use App\Entity\PdfPageTranslation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Manages PDF page translations with intelligent caching.
 * Single Responsibility: Translation retrieval/storage coordination.
 * Strategy: Cache → Database → AI Translation (lazy loading).
 */
final readonly class TranslationService implements TranslationServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheItemPoolInterface $cache,
        private OllamaService $ollama,
        private LanguageDetectorInterface $languageDetector,
        private int $translationCacheTtl
    ) {
    }

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
    ): ?array {
        return $this->findCachedOrStoredTranslation(
            $pdfFilename,
            $pageNumber,
            $originalText,
            $targetLanguage
        );
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
        $existing = $this->findCachedOrStoredTranslation(
            $pdfFilename,
            $pageNumber,
            $originalText,
            $targetLanguage
        );

        if ($existing !== null) {
            return $existing;
        }

        // Generate new translation (slowest path)
        $sourceLanguage = $this->languageDetector->detect($originalText)['language'];
        $cacheKey = $this->buildCacheKey($pdfFilename, $pageNumber, $targetLanguage);

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
     * Shared lookup: detects language, checks cache, then database.
     * Returns a result array on hit, or null if no translation exists yet.
     *
     * @return array{text: string, source: string, source_language: string, cached: bool}|null
     */
    private function findCachedOrStoredTranslation(
        string $pdfFilename,
        int $pageNumber,
        string $originalText,
        string $targetLanguage
    ): ?array {
        $sourceLanguage = $this->languageDetector->detect($originalText)['language'];

        if ($sourceLanguage === $targetLanguage) {
            return [
                'text' => $originalText,
                'source' => 'original',
                'source_language' => $sourceLanguage,
                'cached' => false,
            ];
        }

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

        $translation = $this->findTranslationInDatabase($pdfFilename, $pageNumber, $targetLanguage);

        if ($translation instanceof PdfPageTranslation) {
            $this->storeInCache($cacheKey, $translation->getTranslatedText());

            return [
                'text' => $translation->getTranslatedText(),
                'source' => 'database',
                'source_language' => $sourceLanguage,
                'cached' => false,
            ];
        }

        return null;
    }

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
     * @return array{text: string, source: string, source_language: string, cached: bool}
     */
    private function translateAndStore(
        string $pdfFilename,
        int $pageNumber,
        string $originalText,
        string $sourceLanguage,
        string $targetLanguage,
        string $cacheKey
    ): array {
        $translatedText = $this->ollama->translate($originalText, $targetLanguage);

        $translation = new PdfPageTranslation();
        $translation->setPdfFilename($pdfFilename);
        $translation->setPageNumber($pageNumber);
        $translation->setSourceLanguage($sourceLanguage);
        $translation->setTargetLanguage($targetLanguage);
        $translation->setOriginalText($originalText);
        $translation->setTranslatedText($translatedText);

        $this->entityManager->persist($translation);
        $this->entityManager->flush();

        $this->storeInCache($cacheKey, $translatedText);

        return [
            'text' => $translatedText,
            'source' => 'translated',
            'source_language' => $sourceLanguage,
            'cached' => false,
        ];
    }

    private function storeInCache(string $cacheKey, string $text): void
    {
        $cachedItem = $this->cache->getItem($cacheKey);
        $cachedItem->set($text);
        $cachedItem->expiresAfter($this->translationCacheTtl);
        $this->cache->save($cachedItem);
    }

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
