<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message for async PDF page translation.
 * This message is dispatched to the queue and processed by TranslatePageMessageHandler.
 */
final class TranslatePageMessage
{
    public function __construct(
        private readonly string $pdfFilename,
        private readonly int $pageNumber,
        private readonly string $targetLanguage,
        private readonly string $originalText
    ) {
    }

    public function getPdfFilename(): string
    {
        return $this->pdfFilename;
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    public function getTargetLanguage(): string
    {
        return $this->targetLanguage;
    }

    public function getOriginalText(): string
    {
        return $this->originalText;
    }
}
