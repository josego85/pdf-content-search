<?php

declare(strict_types=1);

namespace App\Command;

use App\Contract\PdfIndexerInterface;
use App\Service\LanguageDetector;
use App\Service\PdfProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:index-pdfs',
    description: 'Index all PDFs from a folder into Elasticsearch (by pages)',
)]
class IndexarPdfsCommand extends Command
{
    private string $pdfFolder = __DIR__ . '/../../public/pdfs';

    public function __construct(
        private readonly PdfIndexerInterface $es,
        private readonly PdfProcessor $pdfProcessor,
        private readonly LanguageDetector $languageDetector
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("<info>📂 Searching PDFs in {$this->pdfFolder}</info>");

        $finder = new Finder();
        $finder->files()->in($this->pdfFolder)->name('*.pdf');

        if (!$finder->hasResults()) {
            $output->writeln('<comment>No PDF files found.</comment>');

            return Command::SUCCESS;
        }

        foreach ($finder as $file) {
            $filename = $file->getFilename();
            $path = $file->getRealPath();
            $pdfId = pathinfo($filename, PATHINFO_FILENAME);

            $output->writeln("📄 Indexing: <info>$filename</info>");

            $totalPages = $this->pdfProcessor->extractPageCount($path);

            if (0 === $totalPages) {
                $output->writeln('<comment>⚠️ Could not determine page count.</comment>');
                continue;
            }

            for ($page = 1; $page <= $totalPages; ++$page) {
                $text = $this->pdfProcessor->extractTextFromPage($path, $page);

                if (!empty($text)) {
                    try {
                        // Detect language
                        $detectionResult = $this->languageDetector->detect($text);
                        $language = $detectionResult['language'];

                        $this->es->indexPdfPage(
                            $pdfId . '_page_' . $page,
                            $filename,
                            $page,
                            $text,
                            '/pdfs/' . $filename,
                            $totalPages,
                            $language
                        );
                    } catch (\Exception $e) {
                        $output->writeln("<error>❌ Error processing $filename page $page: {$e->getMessage()}</error>");
                        continue;
                    }
                }
            }

            $output->writeln('<info>✔️  Indexed</info>');
        }

        $output->writeln('<info>✅ Process completed</info>');

        return Command::SUCCESS;
    }
}
