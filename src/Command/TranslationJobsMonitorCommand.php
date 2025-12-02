<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\TranslationJobRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:translation:monitor',
    description: 'Monitor active translation jobs in real-time'
)]
final class TranslationJobsMonitorCommand extends Command
{
    public function __construct(
        private readonly TranslationJobRepository $jobRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('watch', 'w', InputOption::VALUE_NONE, 'Watch mode: continuously refresh every 2 seconds')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Show all jobs (including completed/failed)')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command monitors active translation jobs.

By default, shows only active jobs (queued/processing):
  <info>php %command.full_name%</info>

Watch mode for continuous monitoring:
  <info>php %command.full_name% --watch</info>

Show all jobs including completed/failed:
  <info>php %command.full_name% --all</info>
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $watchMode = $input->getOption('watch');
        $showAll = $input->getOption('all');

        if ($watchMode) {
            $io->title('Translation Jobs Monitor (Watch Mode)');
            $io->note('Press Ctrl+C to stop monitoring');

            while (true) {
                // Clear screen
                $output->write("\033[2J\033[;H");

                $this->displayJobs($io, $output, $showAll);

                sleep(2);
            }
        } else {
            $io->title('Translation Jobs Monitor');
            $this->displayJobs($io, $output, $showAll);
        }

        return Command::SUCCESS;
    }

    private function displayJobs(SymfonyStyle $io, OutputInterface $output, bool $showAll): void
    {
        $jobs = $showAll
            ? $this->jobRepository->findBy([], ['createdAt' => 'DESC'], 50)
            : $this->jobRepository->findActiveJobs();

        if (empty($jobs)) {
            $io->info('No ' . ($showAll ? '' : 'active ') . 'translation jobs found');

            return;
        }

        $table = new Table($output);
        $table->setHeaders([
            'ID',
            'Status',
            'PDF',
            'Page',
            'Lang',
            'Worker PID',
            'Created',
            'Started',
            'Duration',
            'Error',
        ]);

        $now = new \DateTime();

        foreach ($jobs as $job) {
            $duration = '';

            if ($job->getStatus() === 'processing' && $job->getStartedAt()) {
                $diff = $now->getTimestamp() - $job->getStartedAt()->getTimestamp();
                $duration = $this->formatDuration($diff);
            } elseif ($job->getCompletedAt() && $job->getStartedAt()) {
                $diff = $job->getCompletedAt()->getTimestamp() - $job->getStartedAt()->getTimestamp();
                $duration = $this->formatDuration($diff);
            }

            $status = $this->colorizeStatus($job->getStatus());
            $createdAgo = $this->timeAgo($job->getCreatedAt());
            $startedAgo = $job->getStartedAt() ? $this->timeAgo($job->getStartedAt()) : '-';

            // Truncate filename if too long
            $filename = $job->getPdfFilename();

            if (strlen($filename) > 30) {
                $filename = '...' . substr($filename, -27);
            }

            // Truncate error message if too long
            $error = $job->getErrorMessage() ?? '';

            if (strlen($error) > 40) {
                $error = substr($error, 0, 37) . '...';
            }

            $table->addRow([
                $job->getId(),
                $status,
                $filename,
                $job->getPageNumber(),
                $job->getTargetLanguage(),
                $job->getWorkerPid() ?? '-',
                $createdAgo,
                $startedAgo,
                $duration ?: '-',
                $error ?: '-',
            ]);
        }

        $table->render();

        // Show summary
        $statusCounts = [
            'queued' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
        ];

        foreach ($jobs as $job) {
            ++$statusCounts[$job->getStatus()];
        }

        $io->section('Summary');
        $io->text(sprintf(
            'Queued: <fg=yellow>%d</> | Processing: <fg=cyan>%d</> | Completed: <fg=green>%d</> | Failed: <fg=red>%d</>',
            $statusCounts['queued'],
            $statusCounts['processing'],
            $statusCounts['completed'],
            $statusCounts['failed']
        ));
    }

    private function colorizeStatus(string $status): string
    {
        return match ($status) {
            'queued' => '<fg=yellow>queued</>',
            'processing' => '<fg=cyan>processing</>',
            'completed' => '<fg=green>completed</>',
            'failed' => '<fg=red>failed</>',
            default => $status
        };
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return "{$minutes}m {$remainingSeconds}s";
    }

    private function timeAgo(\DateTimeInterface $datetime): string
    {
        $now = new \DateTime();
        $diff = $now->getTimestamp() - $datetime->getTimestamp();

        if ($diff < 60) {
            return "{$diff}s ago";
        }

        $minutes = floor($diff / 60);

        if ($minutes < 60) {
            return "{$minutes}m ago";
        }

        $hours = floor($minutes / 60);

        return "{$hours}h ago";
    }
}
