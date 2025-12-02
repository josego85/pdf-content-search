<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\TranslationRequestValidator;
use PHPUnit\Framework\TestCase;

final class TranslationRequestValidatorAdditionalTest extends TestCase
{
    private TranslationRequestValidator $validator;

    private string $testPdfsDirectory;

    protected function setUp(): void
    {
        $this->testPdfsDirectory = sys_get_temp_dir() . '/test_pdfs_' . uniqid();
        mkdir($this->testPdfsDirectory);
        $this->validator = new TranslationRequestValidator($this->testPdfsDirectory);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testPdfsDirectory)) {
            array_map('unlink', glob($this->testPdfsDirectory . '/*') ?: []);
            rmdir($this->testPdfsDirectory);
        }
    }

    public function testValidateWithNegativePageNumber(): void
    {
        touch($this->testPdfsDirectory . '/test.pdf');

        $result = $this->validator->validate('test.pdf', -1);

        // -1 is treated as a valid page number (not empty), validation passes
        self::assertTrue($result['valid']);
        self::assertNull($result['error']);
        self::assertNotNull($result['pdfPath']);
        self::assertSame('test.pdf', $result['pdfFilename']);
        self::assertSame(-1, $result['pageNumber']);
    }

    public function testValidateWithZeroPageNumber(): void
    {
        touch($this->testPdfsDirectory . '/test.pdf');

        $result = $this->validator->validate('test.pdf', 0);

        // 0 is considered empty by PHP's empty() function
        self::assertFalse($result['valid']);
        self::assertSame('Missing page number', $result['error']);
        self::assertNull($result['pdfPath']);
        self::assertSame('test.pdf', $result['pdfFilename']);
        self::assertNull($result['pageNumber']);
    }

    public function testValidateWithStringPageNumberThatIsNumeric(): void
    {
        touch($this->testPdfsDirectory . '/test.pdf');

        $result = $this->validator->validate('test.pdf', '5');

        self::assertTrue($result['valid']);
        self::assertNull($result['error']);
        self::assertNotNull($result['pdfPath']);
        self::assertSame('test.pdf', $result['pdfFilename']);
        self::assertSame(5, $result['pageNumber']);
    }

    public function testValidateWithVeryLargePageNumber(): void
    {
        touch($this->testPdfsDirectory . '/test.pdf');

        $result = $this->validator->validate('test.pdf', 999999);

        self::assertTrue($result['valid']);
        self::assertNull($result['error']);
        self::assertSame(999999, $result['pageNumber']);
    }

    public function testValidateWithFilenameContainingSpaces(): void
    {
        touch($this->testPdfsDirectory . '/my document.pdf');

        $result = $this->validator->validate('my document.pdf', 1);

        self::assertTrue($result['valid']);
        self::assertNull($result['error']);
        self::assertSame('my document.pdf', $result['pdfFilename']);
    }

    public function testValidateWithFilenameContainingSpecialCharacters(): void
    {
        touch($this->testPdfsDirectory . '/file_123-test.pdf');

        $result = $this->validator->validate('file_123-test.pdf', 1);

        self::assertTrue($result['valid']);
        self::assertNull($result['error']);
        self::assertSame('file_123-test.pdf', $result['pdfFilename']);
    }

    public function testValidateReturnsCorrectPdfPath(): void
    {
        $filename = 'document.pdf';
        touch($this->testPdfsDirectory . '/' . $filename);

        $result = $this->validator->validate($filename, 1);

        self::assertTrue($result['valid']);
        self::assertStringEndsWith('/document.pdf', $result['pdfPath']);
        self::assertStringContainsString($this->testPdfsDirectory, $result['pdfPath']);
    }
}
