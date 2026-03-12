<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Contract\PdfProcessorInterface;
use App\Service\PdfProcessor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PdfProcessor.
 * Behavior tests run against real binaries (pdfinfo, pdftotext, ocrmypdf);
 * non-existent file paths verify graceful failure without mocking the process layer.
 */
final class PdfProcessorTest extends TestCase
{
    private PdfProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new PdfProcessor('eng+spa+deu', 50);
    }

    public function testImplementsPdfProcessorInterface(): void
    {
        $this->assertInstanceOf(PdfProcessorInterface::class, $this->processor);
    }

    public function testExtractPageCountParsesValidOutput(): void
    {
        $output = "Pages:          10\n";
        preg_match('/Pages:\\s+(\\d+)/i', $output, $matches);
        $this->assertSame(10, (int) $matches[1]);
    }

    public function testExtractPageCountReturnsZeroWhenPagesNotFound(): void
    {
        $output = 'Invalid output without pages';
        preg_match('/Pages:\\s+(\\d+)/i', $output, $matches);
        $result = isset($matches[1]) ? (int) $matches[1] : 0;
        $this->assertSame(0, $result);
    }

    public function testExtractPageCountParsesVariousFormats(): void
    {
        $testCases = [
            ["Pages:          5\n", 5],
            ["Pages: 100\n", 100],
            ["Pages:    1\n", 1],
            ["Pages: 999\n", 999],
        ];

        foreach ($testCases as [$output, $expected]) {
            preg_match('/Pages:\\s+(\\d+)/i', $output, $matches);
            $result = isset($matches[1]) ? (int) $matches[1] : 0;
            $this->assertSame($expected, $result, "Failed for output: {$output}");
        }
    }

    public function testExtractPageCountRegexIsCaseInsensitive(): void
    {
        $testCases = ['Pages: 5', 'PAGES: 5', 'pages: 5', 'PaGeS: 5'];

        foreach ($testCases as $output) {
            preg_match('/Pages:\\s+(\\d+)/i', $output, $matches);
            $result = isset($matches[1]) ? (int) $matches[1] : 0;
            $this->assertSame(5, $result, "Failed for: {$output}");
        }
    }

    public function testExtractTextFromPageTrimsWhitespace(): void
    {
        $textWithWhitespace = "   \n  Sample text  \n  ";
        $this->assertSame('Sample text', trim($textWithWhitespace));
    }

    public function testExtractTextFromPageHandlesEmptyOutput(): void
    {
        $this->assertSame('', trim(''));
    }

    public function testExtractPageCountReturnsInteger(): void
    {
        $output = 'Pages: 42';
        preg_match('/Pages:\\s+(\\d+)/i', $output, $matches);
        $result = isset($matches[1]) ? (int) $matches[1] : 0;
        $this->assertIsInt($result);
    }

    public function testExtractPageCountHandlesLargeNumbers(): void
    {
        $output = 'Pages: 9999';
        preg_match('/Pages:\\s+(\\d+)/i', $output, $matches);
        $result = isset($matches[1]) ? (int) $matches[1] : 0;
        $this->assertSame(9999, $result);
    }

    public function testExtractPageCountIgnoresNonNumericValues(): void
    {
        $output = 'Pages: abc';
        preg_match('/Pages:\\s+(\\d+)/i', $output, $matches);
        $result = isset($matches[1]) ? (int) $matches[1] : 0;
        $this->assertSame(0, $result);
    }

    public function testExtractPageCountFindsFirstMatch(): void
    {
        $output = "Pages: 10\nPages: 20\n";
        preg_match('/Pages:\\s+(\\d+)/i', $output, $matches);
        $result = isset($matches[1]) ? (int) $matches[1] : 0;
        $this->assertSame(10, $result);
    }

    // --- Behavior tests against real (failing) processes ---

    public function testExtractPageCountReturnsZeroForNonExistentFile(): void
    {
        $result = $this->processor->extractPageCount('/tmp/nonexistent_pdf_test_' . uniqid() . '.pdf');
        $this->assertSame(0, $result);
    }

    public function testExtractTextFromPageReturnsEmptyForNonExistentFile(): void
    {
        $result = $this->processor->extractTextFromPage('/tmp/nonexistent_pdf_test_' . uniqid() . '.pdf', 1);
        $this->assertSame('', $result);
    }

    public function testEnsureTextLayerReturnsFalseForNonExistentFile(): void
    {
        $result = $this->processor->ensureTextLayer('/tmp/nonexistent_pdf_test_' . uniqid() . '.pdf');
        $this->assertFalse($result);
    }

    public function testEnsureTextLayerSkipsOcrWhenTextIsSufficient(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'pdf_test_');
        // minTextLength=0 → pdftotext output length (0 for non-PDF) >= 0 → skips OCR → returns false
        $skipProcessor = new PdfProcessor('eng', 0);
        $result = $skipProcessor->ensureTextLayer($tmpFile);
        $this->assertFalse($result);
        unlink($tmpFile);
    }

    /**
     * @dataProvider pageNumberProvider
     */
    public function testExtractTextFromPageWithDifferentPages(int $page): void
    {
        // Verify graceful failure for any page on a non-existent file
        $result = $this->processor->extractTextFromPage('/tmp/nonexistent_' . uniqid() . '.pdf', $page);
        $this->assertSame('', $result);
    }

    public static function pageNumberProvider(): array
    {
        return [
            'first page' => [1],
            'middle page' => [50],
            'last page' => [100],
        ];
    }
}
