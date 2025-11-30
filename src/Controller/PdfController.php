<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PdfProcessor;
use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * PDF viewing and translation controller.
 * Single Responsibility: Handle PDF-related HTTP requests.
 */
final class PdfController extends AbstractController
{
    public function __construct(
        private readonly PdfProcessor $pdfProcessor,
        private readonly TranslationService $translationService
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
     * Translates a PDF page on-demand.
     * Returns cached translation if available, otherwise generates new one.
     */
    #[Route('/api/pdf/translate', name: 'api_pdf_translate', methods: ['POST'])]
    public function translatePage(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $pdfFilename = $data['filename'] ?? null;
            $pageNumber = $data['page'] ?? null;
            $targetLanguage = $data['target_language'] ?? 'es';

            if (!$pdfFilename || !$pageNumber) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Missing filename or page number',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Build PDF path
            $pdfPath = $this->getParameter('kernel.project_dir') . '/public/pdfs/' . $pdfFilename;

            if (!file_exists($pdfPath)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'PDF file not found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Extract original text from PDF
            $originalText = $this->pdfProcessor->extractTextFromPage($pdfPath, (int) $pageNumber);

            if (empty($originalText)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'No text found on this page',
                ], Response::HTTP_NOT_FOUND);
            }

            // Get translation (from cache, DB, or new)
            $result = $this->translationService->getTranslation(
                $pdfFilename,
                (int) $pageNumber,
                $originalText,
                $targetLanguage
            );

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'original_text' => $originalText,
                    'translated_text' => $result['text'],
                    'source_language' => $result['source_language'],
                    'target_language' => $targetLanguage,
                    'cached' => $result['cached'],
                    'source' => $result['source'], // 'cache', 'database', 'translated', 'original'
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Translation error',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
