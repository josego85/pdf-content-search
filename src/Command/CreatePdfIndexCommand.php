<?php

declare(strict_types=1);

namespace App\Command;

use App\Contract\IndexManagementInterface;
use App\Contract\PipelineManagementInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-pdf-index',
    description: 'Creates the PDF index in Elasticsearch with the correct settings and mappings.'
)]
class CreatePdfIndexCommand extends Command
{
    public function __construct(
        private readonly IndexManagementInterface $im,
        private readonly PipelineManagementInterface $pm
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $settings = [
            'settings' => [
                'analysis' => [
                    'analyzer' => [
                        'custom_analyzer' => [
                            'tokenizer' => 'standard',
                            'filter' => [
                                'lowercase',
                                'asciifolding',
                            ],
                        ],
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'title' => [
                        'type' => 'text',
                        'analyzer' => 'custom_analyzer',
                    ],
                    'page' => [
                        'type' => 'integer',
                    ],
                    'text' => [
                        'type' => 'text',
                        'analyzer' => 'custom_analyzer',
                    ],
                    'path' => [
                        'type' => 'keyword',
                    ],
                    'total_pages' => [
                        'type' => 'integer',
                    ],
                    'date' => [
                        'type' => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss||strict_date_optional_time',
                    ],
                    'text_embedding' => [
                        'type' => 'dense_vector',
                        'dims' => 768,
                        'index' => true,
                        'similarity' => 'cosine',
                        'index_options' => [
                            'type' => 'hnsw',
                            'm' => 16,
                            'ef_construction' => 100,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $this->pm->createIngestPipeline('remove_accents');
            $this->im->deleteIndex();
            $this->im->createIndex($settings);
            $output->writeln('<info>Index <comment>pdf_pages</comment> created successfully.</info>');
        } catch (\Exception $e) {
            $output->writeln("<error>Failed to create index: {$e->getMessage()}</error>");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
