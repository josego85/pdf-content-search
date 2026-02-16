<?php

declare(strict_types=1);

namespace App\Command;

use App\Contract\EmbeddingServiceInterface;
use App\Contract\PdfIndexerInterface;
use App\Service\LanguageDetector;
use App\Service\PdfProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        private readonly LanguageDetector $languageDetector,
        private readonly EmbeddingServiceInterface $embeddingService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'skip-embeddings',
            null,
            InputOption::VALUE_NONE,
            'Skip embedding generation (faster, but no semantic search)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $skipEmbeddings = $input->getOption('skip-embeddings');
        $startTime = microtime(true);

        $output->writeln("<info>📂 Searching PDFs in {$this->pdfFolder}</info>");

        if ($skipEmbeddings) {
            $output->writeln('<comment>⚠️  Skipping embedding generation (semantic search disabled)</comment>');
        } else {
            $output->writeln('<info>🧠 Embeddings will be generated for semantic search</info>');
        }

        $finder = new Finder();
        $finder->files()->in($this->pdfFolder)->name('*.pdf');

        if (!$finder->hasResults()) {
            $output->writeln('<comment>No PDF files found.</comment>');

            return Command::SUCCESS;
        }

        // Count total pages first for progress tracking
        $totalPagesCount = 0;
        $filesData = [];
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $pageCount = $this->pdfProcessor->extractPageCount($path);
            $totalPagesCount += $pageCount;
            $filesData[] = [
                'filename' => $file->getFilename(),
                'path' => $path,
                'pages' => $pageCount,
            ];
        }

        $output->writeln(sprintf('<info>Found %d PDFs with %d total pages</info>', count($filesData), $totalPagesCount));
        $output->writeln('');

        // Create progress bar
        $progressBar = new ProgressBar($output, $totalPagesCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        $indexedPages = 0;
        $errorCount = 0;

        foreach ($filesData as $fileData) {
            $filename = $fileData['filename'];
            $path = $fileData['path'];
            $totalPages = $fileData['pages'];
            $pdfId = pathinfo($filename, PATHINFO_FILENAME);

            if (0 === $totalPages) {
                $progressBar->advance();
                ++$errorCount;
                continue;
            }

            // Add text layer to scanned PDFs (enables viewer highlighting)
            if ($this->pdfProcessor->ensureTextLayer($path)) {
                $output->writeln("  <comment>OCR text layer added to {$filename}</comment>");
            }

            for ($page = 1; $page <= $totalPages; ++$page) {
                $text = $this->pdfProcessor->extractTextFromPage($path, $page);

                if (!empty($text)) {
                    try {
                        // Detect language
                        $detectionResult = $this->languageDetector->detect($text);
                        $language = $detectionResult['language'];

                        // Generate embedding if not skipped
                        $embedding = null;

                        if (!$skipEmbeddings) {
                            $embedding = $this->embeddingService->embed($text);
                        }

                        $this->es->indexPdfPage(
                            $pdfId . '_page_' . $page,
                            $filename,
                            $page,
                            $text,
                            '/pdfs/' . $filename,
                            $totalPages,
                            $language,
                            $embedding
                        );

                        ++$indexedPages;
                    } catch (\Exception $e) {
                        ++$errorCount;
                    }
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln('');

        $duration = round(microtime(true) - $startTime, 2);
        $output->writeln(sprintf(
            '<info>✅ Process completed: %d pages indexed, %d errors, %s seconds</info>',
            $indexedPages,
            $errorCount,
            $duration
        ));

        return Command::SUCCESS;
    }
}
