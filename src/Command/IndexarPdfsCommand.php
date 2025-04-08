<?php

namespace App\Command;

use App\Contract\PdfIndexerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
    private string $pdfFolder = __DIR__.'/../../public/pdfs';

    public function __construct(private readonly PdfIndexerInterface $es)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("<info>📂 Searching PDFs in {$this->pdfFolder}</info>");

        $finder = new Finder();
        $finder->files()->in($this->pdfFolder)->name('*.pdf');

        if (! $finder->hasResults()) {
            $output->writeln('<comment>No PDF files found.</comment>');

            return Command::SUCCESS;
        }

        foreach ($finder as $file) {
            $filename = $file->getFilename();
            $path = $file->getRealPath();
            $pdfId = pathinfo($filename, PATHINFO_FILENAME);

            $output->writeln("📄 Indexing: <info>$filename</info>");

            // Get total pages
            $pageCountOutput = shell_exec('pdfinfo '.escapeshellarg($path));
            preg_match('/Pages:\\s+(\\d+)/i', $pageCountOutput, $matches);
            $totalPages = isset($matches[1]) ? (int) $matches[1] : 0;

            if (0 === $totalPages) {
                $output->writeln('<comment>⚠️ Could not determine page count.</comment>');

                continue;
            }

            // Process each page
            for ($page = 1; $page <= $totalPages; ++$page) {
                $text = shell_exec("pdftotext -layout -f $page -l $page ".escapeshellarg($path).' -');

                if (! empty(trim($text))) {
                    try {
                        $this->es->indexPdfPage(
                            $pdfId.'_page_'.$page,
                            $filename,
                            $page,
                            trim($text),
                            '/pdfs/'.$filename,
                            $totalPages
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
