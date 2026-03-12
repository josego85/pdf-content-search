<?php

declare(strict_types=1);

namespace App\Contract;

use Symfony\Component\HttpFoundation\Response;

interface ExportFormatterInterface
{
    /**
     * Builds a downloadable HTTP response for the given format (csv|json).
     *
     * @param array<int, array<mixed>> $rows
     * @param array<int, string> $headers
     */
    public function buildResponse(
        string $format,
        array $rows,
        array $headers,
        string $type,
        int $days
    ): Response;
}
