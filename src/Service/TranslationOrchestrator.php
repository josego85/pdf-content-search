<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TranslationJob;
use App\Message\TranslatePageMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Orchestrates the translation workflow.
 * Single Responsibility: Coordinate translation requests between services.
 *
 * Handles:
 * - Request validation
 * - Text extraction
 * - Translation lookup (cache/DB)
 * - Queue management
 * - Job tracking
 */
final class TranslationOrchestrator
{
    public function __construct(
        private readonly PdfProcessor $pdfProcessor,
        private readonly TranslationService $translationService,
        private readonly TranslationRequestValidator $validator,
        private readonly QueueDuplicationChecker $queueChecker,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Request a translation for a PDF page.
     * Returns existing translation if available, otherwise queues for async processing.
     *
     * @return array{data: array<string, mixed>, status_code: int}
     */
    public function requestTranslation(
        ?string $pdfFilename,
        mixed $pageNumber,
        string $targetLanguage = 'es'
    ): array {
        // Validate request
        $validation = $this->validator->validate($pdfFilename, $pageNumber);

        if (!$validation['valid']) {
            return [
                'data' => [
                    'status' => 'error',
                    'message' => $validation['error'],
                ],
                'status_code' => $validation['error'] === 'PDF file not found'
                    ? Response::HTTP_NOT_FOUND
                    : Response::HTTP_BAD_REQUEST,
            ];
        }

        $pdfFilename = $validation['pdfFilename'];
        $pageNumber = $validation['pageNumber'];
        $pdfPath = $validation['pdfPath'];

        // Extract text from PDF
        $originalText = $this->pdfProcessor->extractTextFromPage($pdfPath, $pageNumber);

        if (empty($originalText)) {
            return [
                'data' => [
                    'status' => 'error',
                    'message' => 'No text found on this page',
                ],
                'status_code' => Response::HTTP_NOT_FOUND,
            ];
        }

        // Check if translation exists (cache/DB)
        $existingTranslation = $this->translationService->findExistingTranslation(
            $pdfFilename,
            $pageNumber,
            $originalText,
            $targetLanguage
        );

        if ($existingTranslation !== null) {
            return [
                'data' => [
                    'status' => 'success',
                    'data' => [
                        'original_text' => $originalText,
                        'translated_text' => $existingTranslation['text'],
                        'source_language' => $existingTranslation['source_language'],
                        'target_language' => $targetLanguage,
                        'cached' => $existingTranslation['cached'],
                        'source' => $existingTranslation['source'],
                    ],
                ],
                'status_code' => Response::HTTP_OK,
            ];
        }

        // Check if already queued
        if ($this->queueChecker->isTranslationQueued($pdfFilename, $pageNumber, $targetLanguage)) {
            return [
                'data' => [
                    'status' => 'queued',
                    'message' => 'Translation already in queue',
                    'already_queued' => true,
                    'data' => [
                        'pdf_filename' => $pdfFilename,
                        'page_number' => $pageNumber,
                        'target_language' => $targetLanguage,
                    ],
                ],
                'status_code' => Response::HTTP_ACCEPTED,
            ];
        }

        // Create job tracking record
        $job = new TranslationJob();
        $job->setPdfFilename($pdfFilename);
        $job->setPageNumber($pageNumber);
        $job->setTargetLanguage($targetLanguage);
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        // Dispatch to queue
        $message = new TranslatePageMessage($pdfFilename, $pageNumber, $targetLanguage, $originalText);
        $this->messageBus->dispatch($message);

        // Mark as queued to prevent duplicates
        $this->queueChecker->markAsQueued($pdfFilename, $pageNumber, $targetLanguage);

        return [
            'data' => [
                'status' => 'queued',
                'message' => 'Translation queued for processing',
                'data' => [
                    'pdf_filename' => $pdfFilename,
                    'page_number' => $pageNumber,
                    'target_language' => $targetLanguage,
                ],
            ],
            'status_code' => Response::HTTP_ACCEPTED,
        ];
    }

    /**
     * Check translation status (polling endpoint).
     * Returns translation if ready, or status if still processing.
     *
     * @return array{data: array<string, mixed>, status_code: int}
     */
    public function checkTranslationStatus(
        ?string $pdfFilename,
        mixed $pageNumber,
        string $targetLanguage = 'es'
    ): array {
        // Validate request
        $validation = $this->validator->validate($pdfFilename, $pageNumber);

        if (!$validation['valid']) {
            return [
                'data' => [
                    'status' => 'error',
                    'message' => $validation['error'],
                ],
                'status_code' => $validation['error'] === 'PDF file not found'
                    ? Response::HTTP_NOT_FOUND
                    : Response::HTTP_BAD_REQUEST,
            ];
        }

        $pdfFilename = $validation['pdfFilename'];
        $pageNumber = $validation['pageNumber'];
        $pdfPath = $validation['pdfPath'];

        // Extract original text
        $originalText = $this->pdfProcessor->extractTextFromPage($pdfPath, $pageNumber);

        if (empty($originalText)) {
            return [
                'data' => [
                    'status' => 'error',
                    'message' => 'No text found on this page',
                ],
                'status_code' => Response::HTTP_NOT_FOUND,
            ];
        }

        // Check if translation is ready (check cache/DB only, don't generate)
        $existingTranslation = $this->translationService->findExistingTranslation(
            $pdfFilename,
            $pageNumber,
            $originalText,
            $targetLanguage
        );

        // If translation is available, return it
        if ($existingTranslation !== null) {
            return [
                'data' => [
                    'status' => 'success',
                    'ready' => true,
                    'data' => [
                        'original_text' => $originalText,
                        'translated_text' => $existingTranslation['text'],
                        'source_language' => $existingTranslation['source_language'],
                        'target_language' => $targetLanguage,
                        'cached' => $existingTranslation['cached'],
                        'source' => $existingTranslation['source'],
                    ],
                ],
                'status_code' => Response::HTTP_OK,
            ];
        }

        // Translation still in progress (not in cache/DB yet)
        return [
            'data' => [
                'status' => 'processing',
                'ready' => false,
                'message' => 'Translation is being processed',
            ],
            'status_code' => Response::HTTP_OK,
        ];
    }
}
