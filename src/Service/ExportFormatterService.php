<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\ExportFormatterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportFormatterService implements ExportFormatterInterface
{
    /**
     * @param array<int, array<mixed>> $rows
     * @param array<int, string> $headers
     */
    public function buildResponse(
        string $format,
        array $rows,
        array $headers,
        string $type,
        int $days
    ): Response {
        $filename = sprintf('analytics-%s-%dd-%s.%s', $type, $days, date('Y-m-d'), $format);

        if ('json' === $format) {
            return $this->buildJsonResponse($rows, $headers, $filename);
        }

        return $this->buildCsvResponse($rows, $headers, $filename);
    }

    /**
     * @param array<int, array<mixed>> $rows
     * @param array<int, string> $headers
     */
    private function buildJsonResponse(array $rows, array $headers, string $filename): Response
    {
        $namedRows = array_map(
            static fn (array $row): array => array_combine($headers, $row),
            $rows
        );

        return new Response(
            (string) json_encode($namedRows, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    /**
     * @param array<int, array<mixed>> $rows
     * @param array<int, string> $headers
     */
    private function buildCsvResponse(array $rows, array $headers, string $filename): StreamedResponse
    {
        return new StreamedResponse(static function () use ($rows, $headers): void {
            $output = fopen('php://output', 'w');
            assert($output !== false);
            fputcsv($output, $headers, escape: '\\');
            foreach ($rows as $row) {
                fputcsv($output, $row, escape: '\\');
            }
            fclose($output);
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
