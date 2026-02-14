<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TranslationJobRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tracks the status of translation jobs in progress.
 * Provides visibility into what workers are currently processing.
 */
#[ORM\Entity(repositoryClass: TranslationJobRepository::class)]
#[ORM\Table(name: 'translation_jobs')]
#[ORM\Index(columns: ['status'], name: 'idx_translation_jobs_status')]
#[ORM\Index(columns: ['pdf_filename', 'page_number', 'target_language'], name: 'idx_translation_jobs_lookup')]
class TranslationJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $pdfFilename = '';

    #[ORM\Column]
    private int $pageNumber = 0;

    #[ORM\Column(length: 10)]
    private string $targetLanguage = 'es';

    #[ORM\Column(length: 20)]
    private string $status = 'queued';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(nullable: true)]
    private ?int $workerPid = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPdfFilename(): string
    {
        return $this->pdfFilename;
    }

    public function setPdfFilename(string $pdfFilename): static
    {
        $this->pdfFilename = $pdfFilename;

        return $this;
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    public function setPageNumber(int $pageNumber): static
    {
        $this->pageNumber = $pageNumber;

        return $this;
    }

    public function getTargetLanguage(): string
    {
        return $this->targetLanguage;
    }

    public function setTargetLanguage(string $targetLanguage): static
    {
        $this->targetLanguage = $targetLanguage;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getWorkerPid(): ?int
    {
        return $this->workerPid;
    }

    public function setWorkerPid(?int $workerPid): static
    {
        $this->workerPid = $workerPid;

        return $this;
    }

    public function markAsProcessing(int $workerPid): void
    {
        $this->status = 'processing';
        $this->startedAt = new \DateTime();
        $this->workerPid = $workerPid;
    }

    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->completedAt = new \DateTime();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->status = 'failed';
        $this->completedAt = new \DateTime();
        $this->errorMessage = $errorMessage;
    }

    public function getDurationSeconds(): ?int
    {
        if (!$this->startedAt) {
            return null;
        }

        $endTime = $this->completedAt ?? new \DateTime();

        return $endTime->getTimestamp() - $this->startedAt->getTimestamp();
    }
}
