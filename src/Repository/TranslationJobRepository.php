<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TranslationJob;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TranslationJob>
 */
class TranslationJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TranslationJob::class);
    }

    /**
     * Find active jobs (queued or processing).
     *
     * @return TranslationJob[]
     */
    public function findActiveJobs(): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.status IN (:statuses)')
            ->setParameter('statuses', ['queued', 'processing'])
            ->orderBy('j.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find existing job for a specific translation request.
     */
    public function findExistingJob(string $pdfFilename, int $pageNumber, string $targetLanguage): ?TranslationJob
    {
        return $this->createQueryBuilder('j')
            ->where('j.pdfFilename = :filename')
            ->andWhere('j.pageNumber = :page')
            ->andWhere('j.targetLanguage = :lang')
            ->andWhere('j.status IN (:statuses)')
            ->setParameter('filename', $pdfFilename)
            ->setParameter('page', $pageNumber)
            ->setParameter('lang', $targetLanguage)
            ->setParameter('statuses', ['queued', 'processing'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Clean up old completed/failed jobs.
     */
    public function cleanupOldJobs(int $olderThanHours = 24): int
    {
        $date = new \DateTime();
        $date->modify("-{$olderThanHours} hours");

        return $this->createQueryBuilder('j')
            ->delete()
            ->where('j.status IN (:statuses)')
            ->andWhere('j.completedAt < :date')
            ->setParameter('statuses', ['completed', 'failed'])
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
