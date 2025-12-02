<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\TranslationOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Translation API controller.
 * Single Responsibility: Handle translation-related HTTP requests.
 */
final class TranslationController extends AbstractController
{
    public function __construct(
        private readonly TranslationOrchestrator $orchestrator
    ) {
    }

    /**
     * Translates a PDF page on-demand.
     * Returns cached translation if available, otherwise queues for async processing.
     */
    #[Route('/api/translations/translate', name: 'api_translate_page', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $result = $this->orchestrator->requestTranslation(
                $data['filename'] ?? null,
                $data['page'] ?? null,
                $data['target_language'] ?? 'es'
            );

            return new JsonResponse($result['data'], $result['status_code']);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Translation error',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Check translation status (polling endpoint).
     * Returns translation if ready, or status if still processing.
     */
    #[Route('/api/translations/status', name: 'api_translation_status', methods: ['POST'])]
    public function status(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $result = $this->orchestrator->checkTranslationStatus(
                $data['filename'] ?? null,
                $data['page'] ?? null,
                $data['target_language'] ?? 'es'
            );

            return new JsonResponse($result['data'], $result['status_code']);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error checking translation status',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
