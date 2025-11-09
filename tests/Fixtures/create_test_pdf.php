<?php

declare(strict_types=1);

/**
 * Simple script to create a test PDF without external dependencies.
 * Uses direct PDF creation with basic PDF structure.
 */
function createTestPdf(string $filename, array $pages): void
{
    $pdf = "%PDF-1.4\n";
    $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

    $pageObjectIds = [];
    $contentObjectIds = [];
    $nextObjId = 3;

    // Create content streams and page objects
    foreach ($pages as $pageNum => $content) {
        $contentObjectIds[] = $nextObjId;
        $contentStream = "BT\n/F1 12 Tf\n50 750 Td\n({$content}) Tj\nET";
        $streamLength = strlen($contentStream);

        $pdf .= "{$nextObjId} 0 obj\n<< /Length {$streamLength} >>\nstream\n{$contentStream}\nendstream\nendobj\n";
        ++$nextObjId;

        $pageObjectIds[] = $nextObjId;
        $pdf .= "{$nextObjId} 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> /Contents " . ($nextObjId - 1) . " 0 R /MediaBox [0 0 612 792] >>\nendobj\n";
        ++$nextObjId;
    }

    // Pages object
    $pageRefs = implode(' ', array_map(static fn ($id) => "{$id} 0 R", $pageObjectIds));
    $pageCount = count($pages);
    $pdf .= "2 0 obj\n<< /Type /Pages /Kids [{$pageRefs}] /Count {$pageCount} >>\nendobj\n";

    // xref table
    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 {$nextObjId}\n";
    $pdf .= "0000000000 65535 f \n";

    // Calculate offsets (simplified - in real PDF these would be exact byte positions)
    for ($i = 1; $i < $nextObjId; ++$i) {
        $pdf .= sprintf("%010d 00000 n \n", 9 + ($i * 50)); // Approximate positions
    }

    $pdf .= "trailer\n<< /Size {$nextObjId} /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

    file_put_contents($filename, $pdf);
}

// Create test PDF
$testPages = [
    1 => 'This is page 1. It contains sample text for testing PDF search functionality. Keywords: machine learning, algorithm, test.',
    2 => 'This is page 2. It has different content about natural language processing and deep learning techniques.',
    3 => 'This is page 3. Final page with information about data science and artificial intelligence applications.',
];

$outputFile = __DIR__ . '/TestPdfs/sample-test.pdf';
createTestPdf($outputFile, $testPages);

echo "Test PDF created successfully at: {$outputFile}\n";
