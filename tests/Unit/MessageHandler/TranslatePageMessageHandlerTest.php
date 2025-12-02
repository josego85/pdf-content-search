<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\TranslationJob;
use App\Message\TranslatePageMessage;
use App\MessageHandler\TranslatePageMessageHandler;
use App\Repository\TranslationJobRepository;
use App\Service\QueueDuplicationChecker;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class TranslatePageMessageHandlerTest extends TestCase
{
    private TranslatePageMessageHandler $handler;

    private TranslationService $translationService;

    private QueueDuplicationChecker $queueChecker;

    private EntityManagerInterface $entityManager;

    private TranslationJobRepository $jobRepository;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->translationService = $this->createMock(TranslationService::class);
        $this->queueChecker = new QueueDuplicationChecker(new ArrayAdapter(), 300);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->jobRepository = $this->createMock(TranslationJobRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new TranslatePageMessageHandler(
            $this->translationService,
            $this->queueChecker,
            $this->entityManager,
            $this->jobRepository,
            $this->logger
        );
    }

    public function testHandleMessageWithExistingJob(): void
    {
        $message = new TranslatePageMessage('test.pdf', 1, 'es', 'Original text');

        $existingJob = new TranslationJob();
        $existingJob->setPdfFilename('test.pdf');
        $existingJob->setPageNumber(1);
        $existingJob->setTargetLanguage('es');

        $this->jobRepository
            ->expects(self::once())
            ->method('findExistingJob')
            ->with('test.pdf', 1, 'es')
            ->willReturn($existingJob);

        $this->translationService
            ->expects(self::once())
            ->method('getTranslation')
            ->with('test.pdf', 1, 'Original text', 'es')
            ->willReturn([
                'text' => 'Texto traducido',
                'source' => 'translated',
                'cached' => false,
                'source_language' => 'en',
            ]);

        $this->entityManager
            ->expects(self::exactly(2))
            ->method('flush');

        $this->logger
            ->expects(self::exactly(2))
            ->method('info');

        ($this->handler)($message);

        self::assertSame('completed', $existingJob->getStatus());
    }

    public function testHandleMessageCreatesNewJob(): void
    {
        $message = new TranslatePageMessage('new.pdf', 1, 'es', 'New text');

        $this->jobRepository
            ->expects(self::once())
            ->method('findExistingJob')
            ->willReturn(null);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(TranslationJob::class));

        $this->translationService
            ->expects(self::once())
            ->method('getTranslation')
            ->willReturn([
                'text' => 'Texto nuevo',
                'source' => 'translated',
                'cached' => false,
                'source_language' => 'en',
            ]);

        $this->entityManager
            ->expects(self::exactly(2))
            ->method('flush');

        ($this->handler)($message);
    }

    public function testHandleMessageWithCachedTranslation(): void
    {
        $message = new TranslatePageMessage('cached.pdf', 1, 'es', 'Cached text');

        $job = new TranslationJob();
        $job->setPdfFilename('cached.pdf');
        $job->setPageNumber(1);
        $job->setTargetLanguage('es');

        $this->jobRepository
            ->method('findExistingJob')
            ->willReturn($job);

        $this->translationService
            ->expects(self::once())
            ->method('getTranslation')
            ->willReturn([
                'text' => 'Texto en caché',
                'source' => 'cache',
                'cached' => true,
                'source_language' => 'en',
            ]);

        $this->logger
            ->expects(self::at(1))
            ->method('info')
            ->with('Translation completed', self::callback(static function ($context) {
                return $context['source'] === 'cache' && $context['cached'] === true;
            }));

        ($this->handler)($message);
    }

    public function testHandleMessageMarksJobAsProcessing(): void
    {
        $message = new TranslatePageMessage('processing.pdf', 1, 'es', 'Text');

        $job = new TranslationJob();
        $job->setPdfFilename('processing.pdf');
        $job->setPageNumber(1);
        $job->setTargetLanguage('es');

        $this->jobRepository
            ->method('findExistingJob')
            ->willReturn($job);

        $this->translationService
            ->method('getTranslation')
            ->willReturn([
                'text' => 'Translated',
                'source' => 'translated',
                'cached' => false,
                'source_language' => 'en',
            ]);

        ($this->handler)($message);

        self::assertNotNull($job->getStartedAt());
        self::assertNotNull($job->getWorkerPid());
    }

    public function testHandleMessageRemovesDuplicationMarker(): void
    {
        $message = new TranslatePageMessage('dedup.pdf', 1, 'es', 'Text');

        $job = new TranslationJob();
        $this->jobRepository
            ->method('findExistingJob')
            ->willReturn($job);

        // Mark as queued first
        $this->queueChecker->markAsQueued('dedup.pdf', 1, 'es');
        self::assertTrue($this->queueChecker->isTranslationQueued('dedup.pdf', 1, 'es'));

        $this->translationService
            ->method('getTranslation')
            ->willReturn([
                'text' => 'Translated',
                'source' => 'translated',
                'cached' => false,
                'source_language' => 'en',
            ]);

        ($this->handler)($message);

        // Should be removed after processing
        self::assertFalse($this->queueChecker->isTranslationQueued('dedup.pdf', 1, 'es'));
    }

    public function testHandleMessageWithTranslationFailure(): void
    {
        $message = new TranslatePageMessage('fail.pdf', 1, 'es', 'Text');

        $job = new TranslationJob();
        $job->setPdfFilename('fail.pdf');
        $job->setPageNumber(1);
        $job->setTargetLanguage('es');

        $this->jobRepository
            ->method('findExistingJob')
            ->willReturn($job);

        $exception = new \RuntimeException('Translation service error');

        $this->translationService
            ->expects(self::once())
            ->method('getTranslation')
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('Translation failed', self::callback(static function ($context) {
                return $context['error'] === 'Translation service error';
            }));

        $this->entityManager
            ->expects(self::exactly(2))
            ->method('flush');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Translation service error');

        ($this->handler)($message);

        self::assertSame('failed', $job->getStatus());
        self::assertSame('Translation service error', $job->getErrorMessage());
    }

    public function testHandleMessageRemovesDuplicationMarkerOnFailure(): void
    {
        $message = new TranslatePageMessage('fail-dedup.pdf', 1, 'es', 'Text');

        $job = new TranslationJob();
        $this->jobRepository
            ->method('findExistingJob')
            ->willReturn($job);

        // Mark as queued
        $this->queueChecker->markAsQueued('fail-dedup.pdf', 1, 'es');

        $this->translationService
            ->method('getTranslation')
            ->willThrowException(new \RuntimeException('Error'));

        try {
            ($this->handler)($message);
        } catch (\RuntimeException $e) {
            // Expected
        }

        // Should be removed even on failure to allow retry
        self::assertFalse($this->queueChecker->isTranslationQueued('fail-dedup.pdf', 1, 'es'));
    }

    public function testHandleMessageLogsStartAndCompletion(): void
    {
        $message = new TranslatePageMessage('log.pdf', 1, 'es', 'Text');

        $job = new TranslationJob();
        $this->jobRepository
            ->method('findExistingJob')
            ->willReturn($job);

        $this->translationService
            ->method('getTranslation')
            ->willReturn([
                'text' => 'Translated',
                'source' => 'database',
                'cached' => false,
                'source_language' => 'en',
            ]);

        $this->logger
            ->expects(self::exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting async translation', self::anything()],
                ['Translation completed', self::anything()]
            );

        ($this->handler)($message);
    }
}
