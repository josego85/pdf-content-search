<?php

namespace App\Command;

use App\Interface\SearchEngineInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:index-pdfs',
    description: 'Index all PDFs from a folder into Elasticsearch',
)]
class IndexarPdfsCommand extends Command
{
    private string $pdfFolder = __DIR__.'/../../var/pdfs';

    public function __construct(private readonly SearchEngineInterface $es)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
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

            $output->writeln("📄 Indexing: <info>$filename</info>");

            try {
                $text = shell_exec('pdftotext '.escapeshellarg($path).' -');

                if (empty(trim($text))) {
                    $output->writeln('<comment>⚠️ PDF content is empty or could not be extracted.</comment>');

                    continue;
                }

                $this->es->index('documents', uniqid(), [
                    'title' => $filename,
                    'content' => $text,
                    'date' => date('Y-m-d H:i:s'),
                ]);

                $output->writeln('<info>✔️  Indexed</info>');
            } catch (\Exception $e) {
                $output->writeln("<error>❌ Error processing $filename: {$e->getMessage()}</error>");
            }
        }

        $output->writeln('<info>✅ Process completed</info>');

        return Command::SUCCESS;
    }
}
