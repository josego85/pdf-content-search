<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Checks for duplicate messages in the Symfony Messenger queue.
 * Single Responsibility: Queue duplication detection.
 *
 * Senior Dev Approach: Uses cache-based deduplication instead of SQL queries.
 * - More performant (cache lookup vs DB query)
 * - More secure (no SQL injection risk)
 * - More reliable (not dependent on serialization format)
 * - Automatically expires (using cache TTL)
 */
final class QueueDuplicationChecker
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly int $dedupTtl
    ) {
    }

    /**
     * Check if a translation is already queued (prevents duplicate messages).
     * Uses cache-based fingerprinting for fast, secure deduplication.
     */
    public function isTranslationQueued(string $pdfFilename, int $pageNumber, string $targetLanguage): bool
    {
        $fingerprint = $this->buildFingerprint($pdfFilename, $pageNumber, $targetLanguage);
        $cacheKey = "queue_dedup_{$fingerprint}";

        $item = $this->cache->getItem($cacheKey);

        return $item->isHit();
    }

    /**
     * Mark a translation as queued (prevents duplicate dispatches).
     * Call this AFTER successfully dispatching to the queue.
     */
    public function markAsQueued(string $pdfFilename, int $pageNumber, string $targetLanguage): void
    {
        $fingerprint = $this->buildFingerprint($pdfFilename, $pageNumber, $targetLanguage);
        $cacheKey = "queue_dedup_{$fingerprint}";

        $item = $this->cache->getItem($cacheKey);
        $item->set(true);
        $item->expiresAfter($this->dedupTtl);
        $this->cache->save($item);
    }

    /**
     * Remove deduplication marker (call after processing completes).
     */
    public function markAsProcessed(string $pdfFilename, int $pageNumber, string $targetLanguage): void
    {
        $fingerprint = $this->buildFingerprint($pdfFilename, $pageNumber, $targetLanguage);
        $cacheKey = "queue_dedup_{$fingerprint}";

        $this->cache->deleteItem($cacheKey);
    }

    /**
     * Build unique fingerprint for translation request.
     */
    private function buildFingerprint(string $pdfFilename, int $pageNumber, string $targetLanguage): string
    {
        return hash('xxh128', "{$pdfFilename}|{$pageNumber}|{$targetLanguage}");
    }
}
