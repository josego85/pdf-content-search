<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Factory;

/**
 * Factory for creating test PDF document data.
 * Provides realistic PDF metadata for testing indexing and processing.
 */
final class PdfDocumentFactory
{
    private const DEFAULT_FILENAME = 'sample-document.pdf';
    private const DEFAULT_PAGE_COUNT = 5;
    private const DEFAULT_CONTENT = 'Sample PDF content for testing search functionality.';

    private string $filename;

    private int $pageCount;

    private array $pageContents;

    private string $path;

    public function __construct()
    {
        $this->reset();
    }

    public static function create(): self
    {
        return new self();
    }

    public function withFilename(string $filename): self
    {
        $this->filename = $filename;
        $this->path = '/pdfs/' . $filename;

        return $this;
    }

    public function withPageCount(int $pageCount): self
    {
        $this->pageCount = $pageCount;

        return $this;
    }

    public function withPageContent(int $page, string $content): self
    {
        $this->pageContents[$page] = $content;

        return $this;
    }

    public function withAllPagesContent(string $content): self
    {
        $this->pageContents = [];
        for ($i = 1; $i <= $this->pageCount; ++$i) {
            $this->pageContents[$i] = $content . " (Page {$i})";
        }

        return $this;
    }

    public function withPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function buildMetadata(): array
    {
        return [
            'filename' => $this->filename,
            'path' => $this->path,
            'page_count' => $this->pageCount,
        ];
    }

    public function buildPageDocument(int $page): array
    {
        if (!isset($this->pageContents[$page])) {
            $content = self::DEFAULT_CONTENT . " (Page {$page})";
        } else {
            $content = $this->pageContents[$page];
        }

        $documentId = str_replace('.pdf', '', $this->filename) . "_page_{$page}";

        return [
            'id' => $documentId,
            'title' => $this->filename,
            'page' => $page,
            'total_pages' => $this->pageCount,
            'text' => $content,
            'path' => $this->path,
            'date' => date('Y-m-d H:i:s'),
        ];
    }

    public function buildAllPages(): array
    {
        $pages = [];
        for ($i = 1; $i <= $this->pageCount; ++$i) {
            $pages[] = $this->buildPageDocument($i);
        }

        return $pages;
    }

    /**
     * Creates a document with search-specific content.
     */
    public static function createSearchable(): self
    {
        return self::create()
            ->withFilename('searchable-test.pdf')
            ->withPageCount(3)
            ->withPageContent(1, 'This document contains information about machine learning algorithms.')
            ->withPageContent(2, 'Deep learning is a subset of machine learning with neural networks.')
            ->withPageContent(3, 'Natural language processing uses machine learning techniques.');
    }

    /**
     * Creates a document with accented characters for testing normalization.
     */
    public static function createWithAccents(): self
    {
        return self::create()
            ->withFilename('acentos-test.pdf')
            ->withPageCount(2)
            ->withPageContent(1, 'El rápido zorro marrón salta sobre el perro perezoso. Ñoño José.')
            ->withPageContent(2, 'Información con acentuación específica: café, résumé, naïve.');
    }

    /**
     * Creates a document with special operators for testing query parsing.
     */
    public static function createWithOperators(): self
    {
        return self::create()
            ->withFilename('operators-test.pdf')
            ->withPageCount(2)
            ->withPageContent(1, 'This document must have required terms and excluded terms.')
            ->withPageContent(2, 'It contains an "exact phrase match" for testing purposes.');
    }

    private function reset(): void
    {
        $this->filename = self::DEFAULT_FILENAME;
        $this->pageCount = self::DEFAULT_PAGE_COUNT;
        $this->pageContents = [];
        $this->path = '/pdfs/' . self::DEFAULT_FILENAME;
    }
}
