<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ElasticsearchService;
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
        private readonly ElasticsearchService $es
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $settings = [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,  // single-node: no replicas → GREEN health
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
                        'index_options' => 'offsets',
                    ],
                    'page' => [
                        'type' => 'integer',
                    ],
                    'text' => [
                        'type' => 'text',
                        'analyzer' => 'custom_analyzer',
                        'index_options' => 'offsets',  // store positions → faster highlighting
                    ],
                    'path' => [
                        'type' => 'keyword',
                    ],
                    'total_pages' => [
                        'type' => 'integer',
                    ],
                    'language' => [
                        'type' => 'keyword',  // explicit mapping — avoids dynamic inference
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
                            'type' => 'int8_hnsw',
                            'm' => 16,
                            'ef_construction' => 100,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $this->es->createIngestPipeline('remove_accents');
            $this->es->deleteIndex();
            $this->es->createIndex($settings);
            $output->writeln('<info>Index <comment>pdf_pages</comment> created successfully.</info>');
        } catch (\Exception $e) {
            $output->writeln("<error>Failed to create index: {$e->getMessage()}</error>");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
