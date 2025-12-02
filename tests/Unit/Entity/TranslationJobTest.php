<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\TranslationJob;
use PHPUnit\Framework\TestCase;

final class TranslationJobTest extends TestCase
{
    public function testNewJobHasDefaultValues(): void
    {
        $job = new TranslationJob();

        self::assertNull($job->getId());
        self::assertNull($job->getPdfFilename());
        self::assertNull($job->getPageNumber());
        self::assertNull($job->getTargetLanguage());
        self::assertSame('queued', $job->getStatus());
        self::assertInstanceOf(\DateTimeInterface::class, $job->getCreatedAt());
        self::assertNull($job->getStartedAt());
        self::assertNull($job->getCompletedAt());
        self::assertNull($job->getErrorMessage());
        self::assertNull($job->getWorkerPid());
    }

    public function testSetAndGetPdfFilename(): void
    {
        $job = new TranslationJob();
        $job->setPdfFilename('test.pdf');

        self::assertSame('test.pdf', $job->getPdfFilename());
    }

    public function testSetAndGetPageNumber(): void
    {
        $job = new TranslationJob();
        $job->setPageNumber(42);

        self::assertSame(42, $job->getPageNumber());
    }

    public function testSetAndGetTargetLanguage(): void
    {
        $job = new TranslationJob();
        $job->setTargetLanguage('es');

        self::assertSame('es', $job->getTargetLanguage());
    }

    public function testMarkAsProcessingSetsStatusAndTime(): void
    {
        $job = new TranslationJob();
        $workerPid = 1234;

        $job->markAsProcessing($workerPid);

        self::assertSame('processing', $job->getStatus());
        self::assertInstanceOf(\DateTimeInterface::class, $job->getStartedAt());
        self::assertSame($workerPid, $job->getWorkerPid());
    }

    public function testMarkAsCompletedSetsStatusAndTime(): void
    {
        $job = new TranslationJob();
        $job->markAsCompleted();

        self::assertSame('completed', $job->getStatus());
        self::assertInstanceOf(\DateTimeInterface::class, $job->getCompletedAt());
    }

    public function testMarkAsFailedSetsStatusTimeAndError(): void
    {
        $job = new TranslationJob();
        $errorMessage = 'Translation failed due to timeout';

        $job->markAsFailed($errorMessage);

        self::assertSame('failed', $job->getStatus());
        self::assertInstanceOf(\DateTimeInterface::class, $job->getCompletedAt());
        self::assertSame($errorMessage, $job->getErrorMessage());
    }

    public function testFullWorkflowFromQueuedToCompleted(): void
    {
        $job = new TranslationJob();
        $job->setPdfFilename('document.pdf');
        $job->setPageNumber(1);
        $job->setTargetLanguage('es');

        // Initial state
        self::assertSame('queued', $job->getStatus());
        self::assertNull($job->getStartedAt());

        // Mark as processing
        $job->markAsProcessing(5678);
        self::assertSame('processing', $job->getStatus());
        self::assertNotNull($job->getStartedAt());
        self::assertSame(5678, $job->getWorkerPid());

        // Mark as completed
        $job->markAsCompleted();
        self::assertSame('completed', $job->getStatus());
        self::assertNotNull($job->getCompletedAt());
    }

    public function testFullWorkflowFromQueuedToFailed(): void
    {
        $job = new TranslationJob();
        $job->setPdfFilename('document.pdf');
        $job->setPageNumber(1);
        $job->setTargetLanguage('es');

        // Mark as processing
        $job->markAsProcessing(9999);
        self::assertSame('processing', $job->getStatus());

        // Mark as failed
        $errorMessage = 'Worker crashed';
        $job->markAsFailed($errorMessage);
        self::assertSame('failed', $job->getStatus());
        self::assertNotNull($job->getCompletedAt());
        self::assertSame($errorMessage, $job->getErrorMessage());
    }

    public function testCreatedAtIsSetAutomatically(): void
    {
        $before = new \DateTime();
        $job = new TranslationJob();
        $after = new \DateTime();

        self::assertGreaterThanOrEqual($before, $job->getCreatedAt());
        self::assertLessThanOrEqual($after, $job->getCreatedAt());
    }

    public function testSetAndGetStatus(): void
    {
        $job = new TranslationJob();
        $job->setStatus('processing');

        self::assertSame('processing', $job->getStatus());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $job = new TranslationJob();
        $customDate = new \DateTime('2024-01-15 10:00:00');
        $job->setCreatedAt($customDate);

        self::assertSame($customDate, $job->getCreatedAt());
    }

    public function testSetAndGetStartedAt(): void
    {
        $job = new TranslationJob();
        $startTime = new \DateTime('2024-01-15 11:00:00');
        $job->setStartedAt($startTime);

        self::assertSame($startTime, $job->getStartedAt());
    }

    public function testSetAndGetCompletedAt(): void
    {
        $job = new TranslationJob();
        $completedTime = new \DateTime('2024-01-15 12:00:00');
        $job->setCompletedAt($completedTime);

        self::assertSame($completedTime, $job->getCompletedAt());
    }

    public function testSetAndGetErrorMessage(): void
    {
        $job = new TranslationJob();
        $job->setErrorMessage('Custom error message');

        self::assertSame('Custom error message', $job->getErrorMessage());
    }

    public function testSetAndGetWorkerPid(): void
    {
        $job = new TranslationJob();
        $job->setWorkerPid(12345);

        self::assertSame(12345, $job->getWorkerPid());
    }

    public function testGetDurationSecondsWhenNotStarted(): void
    {
        $job = new TranslationJob();

        self::assertNull($job->getDurationSeconds());
    }

    public function testGetDurationSecondsWhenStartedButNotCompleted(): void
    {
        $job = new TranslationJob();
        $job->setStartedAt(new \DateTime('-10 seconds'));

        $duration = $job->getDurationSeconds();

        self::assertNotNull($duration);
        self::assertGreaterThanOrEqual(9, $duration);
        self::assertLessThanOrEqual(11, $duration);
    }

    public function testGetDurationSecondsWhenCompleted(): void
    {
        $job = new TranslationJob();
        $startTime = new \DateTime('2024-01-15 10:00:00');
        $completedTime = new \DateTime('2024-01-15 10:02:30');

        $job->setStartedAt($startTime);
        $job->setCompletedAt($completedTime);

        self::assertSame(150, $job->getDurationSeconds());
    }

    public function testFluentInterfaceForAllSetters(): void
    {
        $job = new TranslationJob();

        $result = $job->setPdfFilename('test.pdf')
            ->setPageNumber(1)
            ->setTargetLanguage('es')
            ->setStatus('completed')
            ->setCreatedAt(new \DateTime())
            ->setStartedAt(new \DateTime())
            ->setCompletedAt(new \DateTime())
            ->setErrorMessage('error')
            ->setWorkerPid(999);

        self::assertInstanceOf(TranslationJob::class, $result);
        self::assertSame('test.pdf', $job->getPdfFilename());
        self::assertSame(1, $job->getPageNumber());
        self::assertSame('es', $job->getTargetLanguage());
        self::assertSame('completed', $job->getStatus());
        self::assertSame('error', $job->getErrorMessage());
        self::assertSame(999, $job->getWorkerPid());
    }
}
