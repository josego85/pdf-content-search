<?php

namespace App\Service;

use App\Contract\PdfIndexerInterface;
use App\Contract\SearchEngineInterface;
use App\Shared\Traits\SafeCallerTrait;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchService implements SearchEngineInterface, PdfIndexerInterface
{
    use SafeCallerTrait;

    private Client $client;

    public function __construct(
        string $host,
        private readonly string $pdfPagesIndex
    ) {
        $this->client = ClientBuilder::create()
            ->setSSLVerification(false)
            ->setHosts([$host])
            ->build();
    }

    public function indexDocument(string $index, string $id, array $data): void
    {
        $this->safeCall(
            fn () => $this->client->index([
                'index' => $index,
                'id' => $id,
                'body' => $data,
            ]),
            'Indexing document failed'
        );
    }

    public function indexPdfPage(
        string $id,
        string $title,
        int $page,
        string $text,
        string $path,
        int $totalPages
    ): void {
        $this->indexDocument($this->pdfPagesIndex, $id, [
            'title' => $title,
            'page' => $page,
            'text' => $text,
            'path' => $path,
            'total_pages' => $totalPages,
            'date' => date('Y-m-d H:i:s'),
        ]);
    }

    public function search(array $query): array
    {
        return $this->safeCall(
            fn () => $this->client->search($query)->asArray(),
            'Search query failed'
        );
    }
}
