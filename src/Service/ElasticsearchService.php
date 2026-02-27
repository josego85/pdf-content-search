<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\PdfIndexerInterface;
use App\Contract\SearchEngineInterface;
use App\DTO\PdfPageDocument;
use App\DTO\SearchResult;
use App\Shared\Traits\SafeCallerTrait;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchService implements PdfIndexerInterface, SearchEngineInterface
{
    use SafeCallerTrait;

    private const int BATCH_SIZE = 100;

    private Client $client;

    public function __construct(
        string $host,
        private readonly string $pdfPagesIndex
    ) {
        $parsedUrl = parse_url($host);
        $cleanHost = ($parsedUrl['scheme'] ?? 'http') . '://' .
                     ($parsedUrl['host'] ?? 'localhost') .
                     (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '');

        $clientBuilder = ClientBuilder::create()
            ->setSSLVerification(false)
            ->setHosts([$cleanHost]);

        // Set authentication if credentials are present in URL
        if (isset($parsedUrl['user']) && isset($parsedUrl['pass'])) {
            $clientBuilder->setBasicAuthentication($parsedUrl['user'], $parsedUrl['pass']);
        }

        $this->client = $clientBuilder->build();
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function createIndex(array $settings = []): void
    {
        $params = [
            'index' => $this->pdfPagesIndex,
            'body' => $settings,
        ];
        $this->client->indices()->create($params);
    }

    public function deleteIndex(): void
    {
        $params = ['index' => $this->pdfPagesIndex];

        // Check if index exists before deleting
        if ($this->client->indices()->exists($params)->asBool()) {
            $this->client->indices()->delete($params);
        }
    }

    /**
     * @param PdfPageDocument[] $pages
     */
    public function indexPages(array $pages): void
    {
        if (empty($pages)) {
            return;
        }

        $this->disableRefresh();

        try {
            foreach (array_chunk($pages, self::BATCH_SIZE) as $batch) {
                $this->sendBulkRequest($batch);
            }
        } finally {
            $this->restoreRefresh();
        }
    }

    private function disableRefresh(): void
    {
        $this->safeCall(
            fn () => $this->client->indices()->putSettings([
                'index' => $this->pdfPagesIndex,
                'body' => ['refresh_interval' => '-1'],
            ]),
            'Failed to disable refresh interval'
        );
    }

    private function restoreRefresh(): void
    {
        $this->safeCall(
            fn () => $this->client->indices()->putSettings([
                'index' => $this->pdfPagesIndex,
                'body' => ['refresh_interval' => '1s'],
            ]),
            'Failed to restore refresh interval'
        );

        $this->safeCall(
            fn () => $this->client->indices()->refresh(['index' => $this->pdfPagesIndex]),
            'Failed to refresh index'
        );
    }

    /**
     * @param PdfPageDocument[] $batch
     */
    private function sendBulkRequest(array $batch): void
    {
        $operations = [];

        foreach ($batch as $page) {
            $operations[] = ['index' => ['_index' => $this->pdfPagesIndex, '_id' => $page->id]];

            $document = [
                'title' => $page->title,
                'page' => $page->page,
                'text' => $page->text,
                'path' => $page->path,
                'total_pages' => $page->totalPages,
                'language' => $page->language,
                'date' => date('Y-m-d H:i:s'),
            ];

            if (null !== $page->embedding) {
                $document['text_embedding'] = $page->embedding;
            }

            $operations[] = $document;
        }

        $this->safeCall(
            fn () => $this->client->bulk(['body' => $operations]),
            'Bulk indexing failed'
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    public function search(array $query): SearchResult
    {
        $raw = $this->safeCall(
            fn () => $this->client->search($query)->asArray(),
            'Search query failed'
        );

        return new SearchResult(
            hits: $raw['hits']['hits'] ?? [],
            total: $raw['hits']['total']['value'] ?? 0,
        );
    }
}
