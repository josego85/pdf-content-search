<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

/**
 * Backfill displayed_results_count for historical rows.
 *
 * At the time these rows were recorded the page size was 10,
 * so we approximate with LEAST(results_count, 10).
 * New rows will have the exact value set by the application.
 */
final class Version20260304144442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill displayed_results_count for historical search_analytics rows using LEAST(results_count, 10).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE search_analytics SET displayed_results_count = LEAST(results_count, 10) WHERE displayed_results_count IS NULL');
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Data backfill cannot be safely reversed: application-set values cannot be distinguished from backfilled ones.');
    }
}
