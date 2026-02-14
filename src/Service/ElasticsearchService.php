<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\IndexManagementInterface;
use App\Contract\PdfIndexerInterface;
use App\Contract\PipelineManagementInterface;
use App\Contract\SearchEngineInterface;
use App\Shared\Traits\SafeCallerTrait;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchService implements IndexManagementInterface, PipelineManagementInterface, PdfIndexerInterface, SearchEngineInterface
{
    use SafeCallerTrait;

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

    public function createIngestPipeline(string $pipelineId = 'remove_accents'): void
    {
        $params = [
            'id' => $pipelineId,
            'body' => [
                'processors' => [
                    [
                        'script' => [
                            'lang' => 'painless',
                            'source' => "
                                if (ctx.text != null) {
                                    ctx.text = ctx.text
                                        .replace('á', 'a')
                                        .replace('à', 'a')
                                        .replace('ä', 'a')
                                        .replace('â', 'a')
                                        .replace('ã', 'a')
                                        .replace('å', 'a')
                                        .replace('Á', 'A')
                                        .replace('À', 'A')
                                        .replace('Ä', 'A')
                                        .replace('Â', 'A')
                                        .replace('Ã', 'A')
                                        .replace('Å', 'A')
                                        .replace('é', 'e')
                                        .replace('è', 'e')
                                        .replace('ë', 'e')
                                        .replace('ê', 'e')
                                        .replace('É', 'E')
                                        .replace('È', 'E')
                                        .replace('Ë', 'E')
                                        .replace('Ê', 'E')
                                        .replace('í', 'i')
                                        .replace('ì', 'i')
                                        .replace('ï', 'i')
                                        .replace('î', 'i')
                                        .replace('Í', 'I')
                                        .replace('Ì', 'I')
                                        .replace('Ï', 'I')
                                        .replace('Î', 'I')
                                        .replace('ó', 'o')
                                        .replace('ò', 'o')
                                        .replace('ö', 'o')
                                        .replace('ô', 'o')
                                        .replace('õ', 'o')
                                        .replace('ø', 'o')
                                        .replace('Ó', 'O')
                                        .replace('Ò', 'O')
                                        .replace('Ö', 'O')
                                        .replace('Ô', 'O')
                                        .replace('Õ', 'O')
                                        .replace('Ø', 'O')
                                        .replace('ú', 'u')
                                        .replace('ù', 'u')
                                        .replace('ü', 'u')
                                        .replace('û', 'u')
                                        .replace('Ú', 'U')
                                        .replace('Ù', 'U')
                                        .replace('Ü', 'U')
                                        .replace('Û', 'U');
                                }
                            ",
                        ],
                    ],
                ],
            ],
        ];
        $this->client->ingest()->putPipeline($params);
    }

    public function deleteIngestPipeline(string $pipelineId = 'remove_accents'): void
    {
        $params = ['id' => $pipelineId];
        $this->client->ingest()->deletePipeline($params);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function indexDocument(string $index, string $id, array $data): void
    {
        $this->safeCall(
            fn () => $this->client->index([
                'index' => $index,
                'id' => $id,
                'body' => $data,
                'pipeline' => 'remove_accents',
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
        int $totalPages,
        string $language = 'unknown',
        ?array $embedding = null
    ): void {
        $document = [
            'title' => $title,
            'page' => $page,
            'text' => $text,
            'path' => $path,
            'total_pages' => $totalPages,
            'language' => $language,
            'date' => date('Y-m-d H:i:s'),
        ];

        // Add embedding if provided (for semantic search)
        if ($embedding !== null) {
            $document['text_embedding'] = $embedding;
        }

        $this->indexDocument($this->pdfPagesIndex, $id, $document);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function search(array $query): array
    {
        return $this->safeCall(
            fn () => $this->client->search($query)->asArray(),
            'Search query failed'
        );
    }

    /**
     * Get Elasticsearch client for advanced use cases.
     * Used by ElasticsearchVectorStore to share the same client instance.
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}
