<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Validates translation requests.
 * Single Responsibility: Input validation for translation operations.
 */
final class TranslationRequestValidator
{
    public function __construct(
        private readonly string $pdfsDirectory
    ) {
    }

    /**
     * Validate translation request data.
     *
     * @return array{valid: bool, error: ?string, pdfPath: ?string}
     */
    public function validate(?string $filename, mixed $pageNumber): array
    {
        if (empty($filename)) {
            return ['valid' => false, 'error' => 'Missing filename', 'pdfPath' => null];
        }

        if (empty($pageNumber)) {
            return ['valid' => false, 'error' => 'Missing page number', 'pdfPath' => null];
        }

        $pdfPath = $this->pdfsDirectory . '/' . $filename;

        if (!file_exists($pdfPath)) {
            return ['valid' => false, 'error' => 'PDF file not found', 'pdfPath' => null];
        }

        return ['valid' => true, 'error' => null, 'pdfPath' => $pdfPath];
    }
}
