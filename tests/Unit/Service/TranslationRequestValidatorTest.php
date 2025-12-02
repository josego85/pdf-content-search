<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\TranslationRequestValidator;
use PHPUnit\Framework\TestCase;

final class TranslationRequestValidatorTest extends TestCase
{
    private TranslationRequestValidator $validator;

    private string $testPdfsDirectory;

    protected function setUp(): void
    {
        $this->testPdfsDirectory = sys_get_temp_dir() . '/test_pdfs_validator_' . uniqid();
        mkdir($this->testPdfsDirectory);
        $this->validator = new TranslationRequestValidator($this->testPdfsDirectory);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testPdfsDirectory)) {
            array_map('unlink', glob($this->testPdfsDirectory . '/*'));
            rmdir($this->testPdfsDirectory);
        }
    }

    public function testValidateWithValidInputs(): void
    {
        // Create test PDF
        touch($this->testPdfsDirectory . '/test.pdf');

        $result = $this->validator->validate('test.pdf', 1);

        self::assertTrue($result['valid']);
        self::assertNull($result['error']);
        self::assertSame('test.pdf', $result['pdfFilename']);
        self::assertSame(1, $result['pageNumber']);
        self::assertNotNull($result['pdfPath']);
    }

    public function testValidateWithMissingFilename(): void
    {
        $result = $this->validator->validate('', 1);

        self::assertFalse($result['valid']);
        self::assertSame('Missing filename', $result['error']);
        self::assertNull($result['pdfPath']);
    }

    public function testValidateWithNullFilename(): void
    {
        $result = $this->validator->validate(null, 1);

        self::assertFalse($result['valid']);
        self::assertSame('Missing filename', $result['error']);
    }

    public function testValidateWithMissingPageNumber(): void
    {
        $result = $this->validator->validate('test.pdf', null);

        self::assertFalse($result['valid']);
        self::assertSame('Missing page number', $result['error']);
    }

    public function testValidateWithEmptyPageNumber(): void
    {
        $result = $this->validator->validate('test.pdf', '');

        self::assertFalse($result['valid']);
        self::assertSame('Missing page number', $result['error']);
    }

    public function testValidateWithNonExistentPdf(): void
    {
        $result = $this->validator->validate('missing.pdf', 1);

        self::assertFalse($result['valid']);
        self::assertSame('PDF file not found', $result['error']);
        self::assertNull($result['pdfPath']);
        self::assertSame('missing.pdf', $result['pdfFilename']);
        self::assertSame(1, $result['pageNumber']);
    }

    public function testValidateWithLargePageNumber(): void
    {
        touch($this->testPdfsDirectory . '/large.pdf');

        $result = $this->validator->validate('large.pdf', 999999);

        self::assertTrue($result['valid']);
        self::assertSame(999999, $result['pageNumber']);
    }

    public function testValidatePreservesFilename(): void
    {
        $filename = 'document with spaces.pdf';
        touch($this->testPdfsDirectory . '/' . $filename);

        $result = $this->validator->validate($filename, 1);

        self::assertTrue($result['valid']);
        self::assertSame($filename, $result['pdfFilename']);
    }

    public function testValidateConvertsStringPageNumberToInt(): void
    {
        touch($this->testPdfsDirectory . '/test.pdf');

        $result = $this->validator->validate('test.pdf', '42');

        self::assertTrue($result['valid']);
        self::assertSame(42, $result['pageNumber']);
        self::assertIsInt($result['pageNumber']);
    }
}
