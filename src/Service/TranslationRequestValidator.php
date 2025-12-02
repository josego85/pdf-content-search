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
     * @return array{valid: bool, error: ?string, pdfPath: ?string, pdfFilename: ?string, pageNumber: ?int}
     */
    public function validate(?string $filename, mixed $pageNumber): array
    {
        if (empty($filename)) {
            return [
                'valid' => false,
                'error' => 'Missing filename',
                'pdfPath' => null,
                'pdfFilename' => null,
                'pageNumber' => null,
            ];
        }

        if (empty($pageNumber)) {
            return [
                'valid' => false,
                'error' => 'Missing page number',
                'pdfPath' => null,
                'pdfFilename' => $filename,
                'pageNumber' => null,
            ];
        }

        $pdfPath = $this->pdfsDirectory . '/' . $filename;

        if (!file_exists($pdfPath)) {
            return [
                'valid' => false,
                'error' => 'PDF file not found',
                'pdfPath' => null,
                'pdfFilename' => $filename,
                'pageNumber' => (int) $pageNumber,
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'pdfPath' => $pdfPath,
            'pdfFilename' => $filename,
            'pageNumber' => (int) $pageNumber,
        ];
    }
}
