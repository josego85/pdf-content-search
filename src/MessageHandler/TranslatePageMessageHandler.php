<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\TranslationJob;
use App\Message\TranslatePageMessage;
use App\Repository\TranslationJobRepository;
use App\Service\QueueDuplicationChecker;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles async translation of PDF pages.
 * Processes TranslatePageMessage from the queue.
 */
#[AsMessageHandler]
final class TranslatePageMessageHandler
{
    public function __construct(
        private readonly TranslationService $translationService,
        private readonly QueueDuplicationChecker $queueChecker,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslationJobRepository $jobRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(TranslatePageMessage $message): void
    {
        $startTime = microtime(true);
        $job = null;

        // Find or create job tracking record
        $job = $this->jobRepository->findExistingJob(
            $message->getPdfFilename(),
            $message->getPageNumber(),
            $message->getTargetLanguage()
        );

        if (!$job) {
            $job = new TranslationJob();
            $job->setPdfFilename($message->getPdfFilename());
            $job->setPageNumber($message->getPageNumber());
            $job->setTargetLanguage($message->getTargetLanguage());
            $this->entityManager->persist($job);
        }

        // Mark as processing with worker PID
        $job->markAsProcessing(getmypid());
        $this->entityManager->flush();

        $this->logger->info('Starting async translation', [
            'pdf' => $message->getPdfFilename(),
            'page' => $message->getPageNumber(),
            'target_language' => $message->getTargetLanguage(),
            'worker_pid' => getmypid(),
        ]);

        try {
            // Process translation (will use cache/DB/AI as needed)
            $result = $this->translationService->getTranslation(
                $message->getPdfFilename(),
                $message->getPageNumber(),
                $message->getOriginalText(),
                $message->getTargetLanguage()
            );

            $duration = round(microtime(true) - $startTime, 2);

            // Mark job as completed
            $job->markAsCompleted();
            $this->entityManager->flush();

            $this->logger->info('Translation completed', [
                'pdf' => $message->getPdfFilename(),
                'page' => $message->getPageNumber(),
                'source' => $result['source'],
                'cached' => $result['cached'],
                'duration_seconds' => $duration,
            ]);

            // Remove deduplication marker after successful processing
            $this->queueChecker->markAsProcessed(
                $message->getPdfFilename(),
                $message->getPageNumber(),
                $message->getTargetLanguage()
            );
        } catch (\Exception $e) {
            // Mark job as failed
            if ($job) {
                $job->markAsFailed($e->getMessage());
                $this->entityManager->flush();
            }

            $this->logger->error('Translation failed', [
                'pdf' => $message->getPdfFilename(),
                'page' => $message->getPageNumber(),
                'error' => $e->getMessage(),
            ]);

            // Remove deduplication marker to allow retry
            $this->queueChecker->markAsProcessed(
                $message->getPdfFilename(),
                $message->getPageNumber(),
                $message->getTargetLanguage()
            );

            // Re-throw to mark message as failed in the queue
            throw $e;
        }
    }
}
