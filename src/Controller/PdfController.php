<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\TranslationJob;
use App\Message\TranslatePageMessage;
use App\Repository\TranslationJobRepository;
use App\Service\PdfProcessor;
use App\Service\QueueDuplicationChecker;
use App\Service\TranslationRequestValidator;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * PDF viewing and translation controller.
 * Single Responsibility: Handle PDF-related HTTP requests.
 */
final class PdfController extends AbstractController
{
    public function __construct(
        private readonly PdfProcessor $pdfProcessor,
        private readonly TranslationService $translationService,
        private readonly MessageBusInterface $messageBus,
        private readonly TranslationRequestValidator $validator,
        private readonly QueueDuplicationChecker $queueChecker,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslationJobRepository $jobRepository
    ) {
    }

    #[Route('/viewer', name: 'pdf_viewer', methods: ['GET'])]
    public function viewer(Request $request): Response
    {
        $pdfPath = $request->query->get('path');
        $highlight = $request->query->get('highlight');
        $page = (int) $request->query->get('page', 1);

        return $this->render('pdf/viewer.html.twig', [
            'path' => $pdfPath,
            'highlight' => $highlight,
            'page' => $page,
        ]);
    }

    /**
     * Check translation status (polling endpoint).
     * Returns translation if ready, or status if still processing.
     */
    #[Route('/api/pdf/translation-status', name: 'api_pdf_translation_status', methods: ['POST'])]
    public function checkTranslationStatus(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $pdfFilename = $data['filename'] ?? null;
            $pageNumber = $data['page'] ?? null;
            $targetLanguage = $data['target_language'] ?? 'es';

            // Validate request
            $validation = $this->validator->validate($pdfFilename, $pageNumber);

            if (!$validation['valid']) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $validation['error'],
                ], $validation['error'] === 'PDF file not found' ? Response::HTTP_NOT_FOUND : Response::HTTP_BAD_REQUEST);
            }

            // Extract original text
            $originalText = $this->pdfProcessor->extractTextFromPage($validation['pdfPath'], (int) $pageNumber);

            if (empty($originalText)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'No text found on this page',
                ], Response::HTTP_NOT_FOUND);
            }

            // Check if translation is ready (check cache/DB only, don't generate)
            $existingTranslation = $this->translationService->findExistingTranslation(
                $pdfFilename,
                (int) $pageNumber,
                $originalText,
                $targetLanguage
            );

            // If translation is available, return it
            if ($existingTranslation !== null) {
                return new JsonResponse([
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
                ]);
            }

            // Translation still in progress (not in cache/DB yet)
            return new JsonResponse([
                'status' => 'processing',
                'ready' => false,
                'message' => 'Translation is being processed',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error checking translation status',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Translates a PDF page on-demand.
     * Returns cached translation if available, otherwise queues for async processing.
     */
    #[Route('/api/pdf/translate', name: 'api_pdf_translate', methods: ['POST'])]
    public function translatePage(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $pdfFilename = $data['filename'] ?? null;
            $pageNumber = $data['page'] ?? null;
            $targetLanguage = $data['target_language'] ?? 'es';

            // Validate request
            $validation = $this->validator->validate($pdfFilename, $pageNumber);

            if (!$validation['valid']) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $validation['error'],
                ], $validation['error'] === 'PDF file not found' ? Response::HTTP_NOT_FOUND : Response::HTTP_BAD_REQUEST);
            }

            // Extract text from PDF
            $originalText = $this->pdfProcessor->extractTextFromPage($validation['pdfPath'], (int) $pageNumber);

            if (empty($originalText)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'No text found on this page',
                ], Response::HTTP_NOT_FOUND);
            }

            // Check if translation exists (cache/DB)
            $existingTranslation = $this->translationService->findExistingTranslation(
                $pdfFilename,
                (int) $pageNumber,
                $originalText,
                $targetLanguage
            );

            if ($existingTranslation !== null) {
                return new JsonResponse([
                    'status' => 'success',
                    'data' => [
                        'original_text' => $originalText,
                        'translated_text' => $existingTranslation['text'],
                        'source_language' => $existingTranslation['source_language'],
                        'target_language' => $targetLanguage,
                        'cached' => $existingTranslation['cached'],
                        'source' => $existingTranslation['source'],
                    ],
                ]);
            }

            // Check if already queued
            if ($this->queueChecker->isTranslationQueued($pdfFilename, (int) $pageNumber, $targetLanguage)) {
                return new JsonResponse([
                    'status' => 'queued',
                    'message' => 'Translation already in queue',
                    'already_queued' => true,
                    'data' => [
                        'pdf_filename' => $pdfFilename,
                        'page_number' => (int) $pageNumber,
                        'target_language' => $targetLanguage,
                    ],
                ], Response::HTTP_ACCEPTED);
            }

            // Create job tracking record
            $job = new TranslationJob();
            $job->setPdfFilename($pdfFilename);
            $job->setPageNumber((int) $pageNumber);
            $job->setTargetLanguage($targetLanguage);
            $this->entityManager->persist($job);
            $this->entityManager->flush();

            // Dispatch to queue
            $message = new TranslatePageMessage($pdfFilename, (int) $pageNumber, $targetLanguage, $originalText);
            $this->messageBus->dispatch($message);

            // Mark as queued to prevent duplicates
            $this->queueChecker->markAsQueued($pdfFilename, (int) $pageNumber, $targetLanguage);

            // Return immediately with "queued" status
            return new JsonResponse([
                'status' => 'queued',
                'message' => 'Translation queued for processing',
                'data' => [
                    'pdf_filename' => $pdfFilename,
                    'page_number' => (int) $pageNumber,
                    'target_language' => $targetLanguage,
                ],
            ], Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Translation error',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
