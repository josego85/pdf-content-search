<?php

namespace App\Service;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

class ElasticsearchService
{
    private Client $client;

    public function __construct(string $host)
    {        
        $this->client = ClientBuilder::create()
          ->setSSLVerification(false)
          ->setHosts(['http://elasticsearch:9200'])
          ->build();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function indexDocument(string $index, string $id, array $body): void
    {
        try {
            $this->client->index([
                'index' => $index,
                'id'    => $id,
                'body'  => $body,
            ]);
        } catch (ClientResponseException|ServerResponseException|AuthenticationException $e) {
            throw new \RuntimeException('Error al indexar documento: ' . $e->getMessage());
        }
    }

    public function search(string $index, string $query): array
    {
        try {
            $response = $this->client->search([
                'index' => $index,
                'body'  => [
                    'query' => [
                        'match' => ['content' => $query],
                    ],
                ],
            ]);

            return $response->asArray();
        } catch (ClientResponseException|ServerResponseException|AuthenticationException $e) {
            throw new \RuntimeException('Error en la búsqueda: ' . $e->getMessage());
        }
    }
}