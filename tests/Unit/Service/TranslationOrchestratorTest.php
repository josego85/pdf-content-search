<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\TranslationJob;
use App\Message\TranslatePageMessage;
use App\Repository\TranslationJobRepository;
use App\Service\PdfProcessor;
use App\Service\QueueDuplicationChecker;
use App\Service\TranslationOrchestrator;
use App\Service\TranslationRequestValidator;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class TranslationOrchestratorTest extends TestCase
{
    private TranslationOrchestrator $orchestrator;

    private PdfProcessor $pdfProcessor;

    private TranslationService $translationService;

    private TranslationRequestValidator $validator;

    private QueueDuplicationChecker $queueChecker;

    private MessageBusInterface $messageBus;

    private EntityManagerInterface $entityManager;

    private TranslationJobRepository $jobRepository;

    private string $testPdfsDirectory;

    protected function setUp(): void
    {
        $this->testPdfsDirectory = sys_get_temp_dir() . '/test_pdfs_' . uniqid();
        mkdir($this->testPdfsDirectory);

        $this->pdfProcessor = $this->createMock(PdfProcessor::class);
        $this->translationService = $this->createMock(TranslationService::class);
        $this->validator = new TranslationRequestValidator($this->testPdfsDirectory);
        $this->queueChecker = new QueueDuplicationChecker(new ArrayAdapter(), 300);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->jobRepository = $this->createMock(TranslationJobRepository::class);

        $this->orchestrator = new TranslationOrchestrator(
            $this->pdfProcessor,
            $this->translationService,
            $this->validator,
            $this->queueChecker,
            $this->messageBus,
            $this->entityManager,
            $this->jobRepository
        );
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testPdfsDirectory)) {
            array_map('unlink', glob($this->testPdfsDirectory . '/*'));
            rmdir($this->testPdfsDirectory);
        }
    }

    public function testRequestTranslationWithInvalidInput(): void
    {
        $result = $this->orchestrator->requestTranslation(null, 'invalid', 'es');

        self::assertSame('error', $result['data']['status']);
        self::assertSame('Missing filename', $result['data']['message']);
        self::assertSame(Response::HTTP_BAD_REQUEST, $result['status_code']);
    }

    public function testRequestTranslationWithMissingPdf(): void
    {
        $result = $this->orchestrator->requestTranslation('missing.pdf', 1, 'es');

        self::assertSame('error', $result['data']['status']);
        self::assertSame('PDF file not found', $result['data']['message']);
        self::assertSame(Response::HTTP_NOT_FOUND, $result['status_code']);
    }

    public function testRequestTranslationWithEmptyText(): void
    {
        // Create test PDF file
        $testPdf = $this->testPdfsDirectory . '/test.pdf';
        touch($testPdf);

        $this->pdfProcessor
            ->method('extractTextFromPage')
            ->willReturn('');

        $result = $this->orchestrator->requestTranslation('test.pdf', 1, 'es');

        self::assertSame('error', $result['data']['status']);
        self::assertSame('No text found on this page', $result['data']['message']);
        self::assertSame(Response::HTTP_NOT_FOUND, $result['status_code']);
    }

    public function testRequestTranslationReturnsExistingTranslation(): void
    {
        // Create test PDF file
        $testPdf = $this->testPdfsDirectory . '/test.pdf';
        touch($testPdf);

        $this->pdfProcessor
            ->method('extractTextFromPage')
            ->willReturn('Original text');

        $this->translationService
            ->method('findExistingTranslation')
            ->willReturn([
                'text' => 'Texto traducido',
                'source_language' => 'en',
                'cached' => true,
                'source' => 'cache',
            ]);

        $result = $this->orchestrator->requestTranslation('test.pdf', 1, 'es');

        self::assertSame('success', $result['data']['status']);
        self::assertSame('Texto traducido', $result['data']['data']['translated_text']);
        self::assertSame('Original text', $result['data']['data']['original_text']);
        self::assertTrue($result['data']['data']['cached']);
        self::assertSame(Response::HTTP_OK, $result['status_code']);
    }

    public function testRequestTranslationReturnsQueuedWhenAlreadyQueued(): void
    {
        // Create test PDF file
        $testPdf = $this->testPdfsDirectory . '/test.pdf';
        touch($testPdf);

        $this->pdfProcessor
            ->method('extractTextFromPage')
            ->willReturn('Original text');

        $this->translationService
            ->method('findExistingTranslation')
            ->willReturn(null);

        // Mark as already queued
        $this->queueChecker->markAsQueued('test.pdf', 1, 'es');

        $result = $this->orchestrator->requestTranslation('test.pdf', 1, 'es');

        self::assertSame('queued', $result['data']['status']);
        self::assertSame('Translation already in queue', $result['data']['message']);
        self::assertTrue($result['data']['already_queued']);
        self::assertSame(Response::HTTP_ACCEPTED, $result['status_code']);
    }

    public function testRequestTranslationQueuesNewTranslation(): void
    {
        // Create test PDF file
        $testPdf = $this->testPdfsDirectory . '/test.pdf';
        touch($testPdf);

        $this->pdfProcessor
            ->method('extractTextFromPage')
            ->willReturn('Original text');

        $this->translationService
            ->method('findExistingTranslation')
            ->willReturn(null);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(TranslationJob::class));

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->messageBus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TranslatePageMessage::class))
            ->willReturn(new Envelope(new TranslatePageMessage('test.pdf', 1, 'es', 'Original text')));

        $result = $this->orchestrator->requestTranslation('test.pdf', 1, 'es');

        self::assertSame('queued', $result['data']['status']);
        self::assertSame('Translation queued for processing', $result['data']['message']);
        self::assertSame(Response::HTTP_ACCEPTED, $result['status_code']);
    }

    public function testCheckTranslationStatusWithInvalidInput(): void
    {
        $result = $this->orchestrator->checkTranslationStatus('', null, 'es');

        self::assertSame('error', $result['data']['status']);
        self::assertSame('Missing filename', $result['data']['message']);
        self::assertSame(Response::HTTP_BAD_REQUEST, $result['status_code']);
    }

    public function testCheckTranslationStatusReturnsReadyTranslation(): void
    {
        // Create test PDF file
        $testPdf = $this->testPdfsDirectory . '/test.pdf';
        touch($testPdf);

        $this->pdfProcessor
            ->method('extractTextFromPage')
            ->willReturn('Original text');

        $this->translationService
            ->method('findExistingTranslation')
            ->willReturn([
                'text' => 'Texto traducido',
                'source_language' => 'en',
                'cached' => false,
                'source' => 'database',
            ]);

        $result = $this->orchestrator->checkTranslationStatus('test.pdf', 1, 'es');

        self::assertSame('success', $result['data']['status']);
        self::assertTrue($result['data']['ready']);
        self::assertSame('Texto traducido', $result['data']['data']['translated_text']);
        self::assertSame(Response::HTTP_OK, $result['status_code']);
    }

    public function testCheckTranslationStatusReturnsProcessing(): void
    {
        // Create test PDF file
        $testPdf = $this->testPdfsDirectory . '/test.pdf';
        touch($testPdf);

        $this->pdfProcessor
            ->method('extractTextFromPage')
            ->willReturn('Original text');

        $this->translationService
            ->method('findExistingTranslation')
            ->willReturn(null);

        $result = $this->orchestrator->checkTranslationStatus('test.pdf', 1, 'es');

        self::assertSame('processing', $result['data']['status']);
        self::assertFalse($result['data']['ready']);
        self::assertSame('Translation is being processed', $result['data']['message']);
        self::assertSame(Response::HTTP_OK, $result['status_code']);
    }

    public function testRequestTranslationWithEmptyFilename(): void
    {
        $result = $this->orchestrator->requestTranslation('', 1, 'es');

        self::assertSame('error', $result['data']['status']);
        self::assertSame(Response::HTTP_BAD_REQUEST, $result['status_code']);
    }

    public function testCheckTranslationStatusWithMissingPdf(): void
    {
        $result = $this->orchestrator->checkTranslationStatus('nonexistent.pdf', 1, 'es');

        self::assertSame('error', $result['data']['status']);
        self::assertSame('PDF file not found', $result['data']['message']);
        self::assertSame(Response::HTTP_NOT_FOUND, $result['status_code']);
    }

    public function testCheckTranslationStatusWithEmptyText(): void
    {
        $testPdf = $this->testPdfsDirectory . '/test.pdf';
        touch($testPdf);

        $this->pdfProcessor
            ->method('extractTextFromPage')
            ->willReturn('');

        $result = $this->orchestrator->checkTranslationStatus('test.pdf', 1, 'es');

        self::assertSame('error', $result['data']['status']);
        self::assertSame('No text found on this page', $result['data']['message']);
        self::assertSame(Response::HTTP_NOT_FOUND, $result['status_code']);
    }

    public function testRequestTranslationDispatchesMessage(): void
    {
        $testPdf = $this->testPdfsDirectory . '/test.pdf';
        touch($testPdf);

        $this->pdfProcessor
            ->method('extractTextFromPage')
            ->willReturn('Test content');

        $this->translationService
            ->method('findExistingTranslation')
            ->willReturn(null);

        $this->messageBus
            ->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new TranslatePageMessage('test.pdf', 1, 'es', 'Test content')));

        $result = $this->orchestrator->requestTranslation('test.pdf', 1, 'es');

        self::assertSame('queued', $result['data']['status']);
    }
}
