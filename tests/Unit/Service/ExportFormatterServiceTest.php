<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Contract\ExportFormatterInterface;
use App\Service\ExportFormatterService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportFormatterServiceTest extends TestCase
{
    private ExportFormatterService $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ExportFormatterService();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(ExportFormatterInterface::class, $this->formatter);
    }

    public function testBuildJsonResponse(): void
    {
        $rows = [
            ['query1', 5],
            ['query2', 3],
        ];
        $headers = ['query', 'count'];

        $response = $this->formatter->buildResponse('json', $rows, $headers, 'top-queries', 7);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.json', (string) $response->headers->get('Content-Disposition'));
    }

    public function testBuildJsonResponseBodyContainsNamedKeys(): void
    {
        $rows = [['search terms', 10]];
        $headers = ['query', 'count'];

        $response = $this->formatter->buildResponse('json', $rows, $headers, 'top-queries', 7);

        $body = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('query', $body[0]);
        $this->assertArrayHasKey('count', $body[0]);
        $this->assertSame('search terms', $body[0]['query']);
        $this->assertSame(10, $body[0]['count']);
    }

    public function testBuildCsvResponse(): void
    {
        $rows = [['query1', 5]];
        $headers = ['query', 'count'];

        $response = $this->formatter->buildResponse('csv', $rows, $headers, 'top-queries', 7);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.csv', (string) $response->headers->get('Content-Disposition'));
    }

    public function testFilenameContainsTypeAndDays(): void
    {
        $response = $this->formatter->buildResponse('json', [], [], 'trends', 30);

        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('trends', $disposition);
        $this->assertStringContainsString('30d', $disposition);
    }

    public function testFilenameContainsCurrentDate(): void
    {
        $response = $this->formatter->buildResponse('csv', [], [], 'top-queries', 7);

        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString(date('Y-m-d'), $disposition);
    }

    public function testBuildJsonResponseWithEmptyRows(): void
    {
        $response = $this->formatter->buildResponse('json', [], [], 'top-queries', 7);

        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame([], $body);
    }

    public function testUnknownFormatDefaultsToCsv(): void
    {
        $response = $this->formatter->buildResponse('xml', [], [], 'top-queries', 7);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }
}
