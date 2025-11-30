<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stores translations of PDF pages.
 * Single Responsibility: Represent a single PDF page translation.
 */
#[ORM\Entity]
#[ORM\Table(name: 'pdf_page_translations')]
#[ORM\Index(name: 'idx_lookup', columns: ['pdf_filename', 'page_number', 'target_language'])]
#[ORM\UniqueConstraint(
    name: 'unique_translation',
    columns: ['pdf_filename', 'page_number', 'source_language', 'target_language']
)]
class PdfPageTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $pdfFilename;

    #[ORM\Column(type: Types::INTEGER)]
    private int $pageNumber;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $sourceLanguage;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $targetLanguage;

    #[ORM\Column(type: Types::TEXT)]
    private string $originalText;

    #[ORM\Column(type: Types::TEXT)]
    private string $translatedText;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPdfFilename(): string
    {
        return $this->pdfFilename;
    }

    public function setPdfFilename(string $pdfFilename): self
    {
        $this->pdfFilename = $pdfFilename;

        return $this;
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    public function setPageNumber(int $pageNumber): self
    {
        $this->pageNumber = $pageNumber;

        return $this;
    }

    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    public function setSourceLanguage(string $sourceLanguage): self
    {
        $this->sourceLanguage = $sourceLanguage;

        return $this;
    }

    public function getTargetLanguage(): string
    {
        return $this->targetLanguage;
    }

    public function setTargetLanguage(string $targetLanguage): self
    {
        $this->targetLanguage = $targetLanguage;

        return $this;
    }

    public function getOriginalText(): string
    {
        return $this->originalText;
    }

    public function setOriginalText(string $originalText): self
    {
        $this->originalText = $originalText;

        return $this;
    }

    public function getTranslatedText(): string
    {
        return $this->translatedText;
    }

    public function setTranslatedText(string $translatedText): self
    {
        $this->translatedText = $translatedText;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
