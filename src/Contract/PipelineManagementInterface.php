<?php

declare(strict_types=1);

namespace App\Contract;

interface PipelineManagementInterface
{
    public function createIngestPipeline(string $pipelineId): void;

    public function deleteIngestPipeline(string $pipelineId): void;
}
