<?php

declare(strict_types=1);

namespace App\Command;

use App\Contract\EmbeddingServiceInterface;
use App\Contract\LanguageDetectorInterface;
use App\Contract\PdfIndexerInterface;
use App\Contract\PdfProcessorInterface;
use App\DTO\PdfPageDocument;
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
    /**
     * Maximum pages sent to Elasticsearch in a single bulk request.
     * Keep at 500 — raising it increases memory without meaningful ES gain.
     */
    private const int FLUSH_SIZE = 500;

    /**
     * Texts sent to Ollama in a single /api/embed call.
     * Batching reduces HTTP round-trips from N pages to N/EMBED_BATCH_SIZE.
     */
    private const int EMBED_BATCH_SIZE = 50;

    private string $pdfFolder = __DIR__ . '/../../public/pdfs';

    public function __construct(
        private readonly PdfIndexerInterface $indexer,
        private readonly PdfProcessorInterface $pdfProcessor,
        private readonly LanguageDetectorInterface $languageDetector,
        private readonly EmbeddingServiceInterface $embeddingService,
        private readonly int $embedMaxChars = 2000,
        private readonly int $embedConcurrency = 1,
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
        $skipEmbeddings = (bool) $input->getOption('skip-embeddings');
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

        $progressBar = new ProgressBar($output, $totalPagesCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        /**
         * Two-stage pipeline:
         *
         * 1. $embedQueue  — raw page data waiting to be embedded (max EMBED_BATCH_SIZE entries)
         * 2. $esBuffer    — fully-built PdfPageDocument objects waiting to be flushed to ES
         *
         * When $embedQueue reaches EMBED_BATCH_SIZE, all texts are sent to Ollama in a
         * single /api/embed call, PdfPageDocuments are created, and the results are pushed
         * into $esBuffer. When $esBuffer reaches FLUSH_SIZE it is bulk-indexed into ES.
         *
         * @var array<int, array{id: string, title: string, page: int, text: string, path: string, totalPages: int, language: string}> $embedQueue
         */
        $embedQueue = [];

        /** @var PdfPageDocument[] $esBuffer */
        $esBuffer = [];

        $errorCount = 0;
        $indexedPages = 0;

        // Phase timers
        $timePdftotext = 0.0;
        $timeEmbedding = 0.0;
        $timeElastic = 0.0;
        $embedBatches = 0;

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

            if ($this->pdfProcessor->ensureTextLayer($path)) {
                $output->writeln("  <comment>OCR text layer added to {$filename}</comment>");
            }

            // Single pdftotext call for the entire PDF — ~20x faster than one call per page
            $t = microtime(true);
            $allPageTexts = $this->pdfProcessor->extractAllPages($path);
            $timePdftotext += microtime(true) - $t;

            for ($page = 1; $page <= $totalPages; ++$page) {
                $text = $allPageTexts[$page] ?? '';

                if ($text !== '') {
                    $embedQueue[] = [
                        'id' => $pdfId . '_page_' . $page,
                        'title' => $filename,
                        'page' => $page,
                        'text' => $text,
                        'path' => '/pdfs/' . $filename,
                        'totalPages' => $totalPages,
                        'language' => $this->languageDetector->detect($text)['language'],
                    ];
                }

                $progressBar->advance();

                if (count($embedQueue) >= self::EMBED_BATCH_SIZE * $this->embedConcurrency) {
                    $t = microtime(true);
                    [$docs, $errors] = $this->embedAndBuild($embedQueue, $skipEmbeddings);
                    $timeEmbedding += microtime(true) - $t;
                    ++$embedBatches;
                    $errorCount += $errors;
                    $embedQueue = [];

                    foreach ($docs as $doc) {
                        $esBuffer[] = $doc;
                    }

                    if (count($esBuffer) >= self::FLUSH_SIZE) {
                        $t = microtime(true);
                        [$flushed, $flushErrors] = $this->flushPages($esBuffer);
                        $timeElastic += microtime(true) - $t;
                        $indexedPages += $flushed;
                        $errorCount += $flushErrors;
                        $esBuffer = [];
                    }
                }
            }
        }

        // Flush remaining embed queue
        if ([] !== $embedQueue) {
            $t = microtime(true);
            [$docs, $errors] = $this->embedAndBuild($embedQueue, $skipEmbeddings);
            $timeEmbedding += microtime(true) - $t;
            ++$embedBatches;
            $errorCount += $errors;
            foreach ($docs as $doc) {
                $esBuffer[] = $doc;
            }
        }

        // Flush remaining ES buffer
        $t = microtime(true);
        [$flushed, $errors] = $this->flushPages($esBuffer);
        $timeElastic += microtime(true) - $t;
        $indexedPages += $flushed;
        $errorCount += $errors;

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
        $output->writeln('');
        $output->writeln('<comment>⏱  Phase breakdown:</comment>');
        $output->writeln(sprintf('   pdftotext  : %6.2f s', $timePdftotext));
        $output->writeln(sprintf('   embeddings : %6.2f s  (%d groups × %d concurrent batches × %d texts)', $timeEmbedding, $embedBatches, $this->embedConcurrency, self::EMBED_BATCH_SIZE));
        $output->writeln(sprintf('   elasticsearch: %4.2f s', $timeElastic));
        $output->writeln(sprintf('   other      : %6.2f s  (language detect, queue overhead, etc.)', $duration - $timePdftotext - $timeEmbedding - $timeElastic));

        return Command::SUCCESS;
    }

    /**
     * Generates embeddings for a batch of queued pages and builds PdfPageDocuments.
     * Sends all texts in a single Ollama /api/embed call to minimise HTTP round-trips.
     *
     * @param array<int, array{id: string, title: string, page: int, text: string, path: string, totalPages: int, language: string}> $queue
     *
     * @return array{0: PdfPageDocument[], 1: int} [documents, error count]
     */
    private function embedAndBuild(array $queue, bool $skipEmbeddings): array
    {
        $docs = [];
        $errors = 0;

        if ($skipEmbeddings) {
            foreach ($queue as $entry) {
                $docs[] = new PdfPageDocument(
                    id: $entry['id'],
                    title: $entry['title'],
                    page: $entry['page'],
                    text: $entry['text'],
                    path: $entry['path'],
                    totalPages: $entry['totalPages'],
                    language: $entry['language'],
                );
            }

            return [$docs, $errors];
        }

        // Split queue into chunks of EMBED_BATCH_SIZE and fire all HTTP requests concurrently.
        // Ollama processes them in parallel when OLLAMA_NUM_PARALLEL >= embedConcurrency.
        // Truncate each text: fewer tokens = proportionally faster inference.
        // Full text is preserved in $entry['text'] for Elasticsearch lexical (BM25) indexing.
        $chunks = array_chunk($queue, self::EMBED_BATCH_SIZE, preserve_keys: true);

        $batches = [];
        foreach ($chunks as $chunkIndex => $chunk) {
            $batches[$chunkIndex] = array_map(
                fn (array $entry): string => mb_substr($entry['text'], 0, $this->embedMaxChars),
                $chunk,
            );
        }

        try {
            $batchResults = $this->embeddingService->embedConcurrentBatches($batches);
        } catch (\Exception) {
            // Embedding failed — index pages without embeddings
            // (lexical search still works; semantic search will not for these pages).
            $errors += count($queue);
            foreach ($queue as $entry) {
                $docs[] = new PdfPageDocument(
                    id: $entry['id'],
                    title: $entry['title'],
                    page: $entry['page'],
                    text: $entry['text'],
                    path: $entry['path'],
                    totalPages: $entry['totalPages'],
                    language: $entry['language'],
                );
            }

            return [$docs, $errors];
        }

        // Flatten chunk results back to a flat embedding array keyed by original queue index.
        $embeddings = [];
        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkEmbeddings = $batchResults[$chunkIndex] ?? [];
            $chunkKeys = array_keys($chunk);
            foreach ($chunkEmbeddings as $posInChunk => $embedding) {
                $originalIndex = $chunkKeys[$posInChunk] ?? null;

                if (null !== $originalIndex) {
                    $embeddings[$originalIndex] = $embedding;
                }
            }
        }

        foreach ($queue as $i => $entry) {
            $docs[] = new PdfPageDocument(
                id: $entry['id'],
                title: $entry['title'],
                page: $entry['page'],
                text: $entry['text'],
                path: $entry['path'],
                totalPages: $entry['totalPages'],
                language: $entry['language'],
                embedding: $embeddings[$i] ?? null,
            );
        }

        return [$docs, $errors];
    }

    /**
     * @param PdfPageDocument[] $pages
     *
     * @return array{0: int, 1: int} [indexed, errors]
     */
    private function flushPages(array $pages): array
    {
        if ($pages === []) {
            return [0, 0];
        }

        try {
            $this->indexer->indexPages($pages);

            return [count($pages), 0];
        } catch (\Exception) {
            return [0, count($pages)];
        }
    }
}
