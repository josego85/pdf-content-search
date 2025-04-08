<?php

namespace App\Service;

use App\Interface\SearchEngineInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

class ElasticsearchService implements SearchEngineInterface
{
    private Client $client;

    public function __construct(string $host)
    {        
        $this->client = ClientBuilder::create()
          ->setSSLVerification(false)
          ->setHosts([$host])
          ->build();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function index(string $index, string $id, array $data): void
    {
        try {
            $this->client->index([
                'index' => $index,
                'id'    => $id,
                'body'  => $data,
            ]);
        } catch (ClientResponseException|ServerResponseException|AuthenticationException $e) {
            throw new \RuntimeException('Error indexing document: ' . $e->getMessage());
        }
    }

    public function search(array $params): array
    {
        try {
            return $this->client->search($params)->asArray();
        } catch (ClientResponseException|ServerResponseException|AuthenticationException $e) {
            throw new \RuntimeException('Elasticsearch error: ' . $e->getMessage());
        }
    }
}