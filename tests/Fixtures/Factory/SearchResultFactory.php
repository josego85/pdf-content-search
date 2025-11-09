<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Factory;

/**
 * Factory for creating test search result data.
 * Follows the Builder pattern for flexible test data generation.
 */
final class SearchResultFactory
{
    private const DEFAULT_TITLE = 'test-document.pdf';
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_TOTAL_PAGES = 10;
    private const DEFAULT_TEXT = 'This is a sample text content for testing purposes.';
    private const DEFAULT_HIGHLIGHT = '<mark>sample</mark> text';
    private const DEFAULT_SCORE = 1.5;

    private string $id;
    private string $title;
    private int $page;
    private int $totalPages;
    private string $text;
    private string $path;
    private array $highlight;
    private float $score;

    public function __construct()
    {
        $this->reset();
    }

    public static function create(): self
    {
        return new self();
    }

    public function withId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function withTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function withPage(int $page): self
    {
        $this->page = $page;
        return $this;
    }

    public function withTotalPages(int $totalPages): self
    {
        $this->totalPages = $totalPages;
        return $this;
    }

    public function withText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function withPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function withHighlight(array $highlight): self
    {
        $this->highlight = $highlight;
        return $this;
    }

    public function withScore(float $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function build(): array
    {
        $result = [
            '_id' => $this->id,
            '_score' => $this->score,
            '_source' => [
                'title' => $this->title,
                'page' => $this->page,
                'total_pages' => $this->totalPages,
                'text' => $this->text,
                'path' => $this->path,
            ],
        ];

        if (!empty($this->highlight)) {
            $result['highlight'] = $this->highlight;
        }

        return $result;
    }

    /**
     * Creates a complete Elasticsearch response structure
     */
    public function buildEsResponse(array $hits = null): array
    {
        if ($hits === null) {
            $hits = [$this->build()];
        }

        return [
            'took' => 5,
            'timed_out' => false,
            '_shards' => [
                'total' => 1,
                'successful' => 1,
                'skipped' => 0,
                'failed' => 0,
            ],
            'hits' => [
                'total' => [
                    'value' => count($hits),
                    'relation' => 'eq',
                ],
                'max_score' => count($hits) > 0 ? $hits[0]['_score'] : 0,
                'hits' => $hits,
            ],
        ];
    }

    /**
     * Creates multiple results with sequential pages
     */
    public static function createMultiple(int $count, string $baseTitle = self::DEFAULT_TITLE): array
    {
        $results = [];
        for ($i = 1; $i <= $count; $i++) {
            $results[] = self::create()
                ->withId("{$baseTitle}_page_{$i}")
                ->withTitle($baseTitle)
                ->withPage($i)
                ->withScore(1.0 / $i)
                ->build();
        }
        return $results;
    }

    private function reset(): void
    {
        $this->id = self::DEFAULT_TITLE . '_page_1';
        $this->title = self::DEFAULT_TITLE;
        $this->page = self::DEFAULT_PAGE;
        $this->totalPages = self::DEFAULT_TOTAL_PAGES;
        $this->text = self::DEFAULT_TEXT;
        $this->path = '/pdfs/' . self::DEFAULT_TITLE;
        $this->highlight = ['text' => [self::DEFAULT_HIGHLIGHT]];
        $this->score = self::DEFAULT_SCORE;
    }
}
