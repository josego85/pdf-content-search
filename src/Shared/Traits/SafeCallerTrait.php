<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use Elastic\Elasticsearch\Exception\ElasticsearchException;

trait SafeCallerTrait
{
    protected function safeCall(callable $fn, string $errorMessage): mixed
    {
        try {
            return $fn();
        } catch (ElasticsearchException $e) {
            throw new \RuntimeException($errorMessage . ': ' . $e->getMessage(), 0, $e);
        }
    }
}
