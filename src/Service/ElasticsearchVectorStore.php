<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\VectorStoreInterface;

final readonly class ElasticsearchVectorStore implements VectorStoreInterface
{
    private const string VECTOR_FIELD = 'text_embedding';

    public function getVectorFieldName(): string
    {
        return self::VECTOR_FIELD;
    }
}
