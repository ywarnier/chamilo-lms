<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\PerformanceAppraisalReminder;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PerformanceAppraisalReminder>
 */
class PerformanceAppraisalReminderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerformanceAppraisalReminder::class);
    }

    /**
     * @return PerformanceAppraisalReminder[]
     */
    public function findDue(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.sent = :sent')
            ->andWhere('r.scheduledAt <= :now')
            ->setParameter('sent', false)
            ->setParameter('now', new DateTime(), Types::DATETIME_MUTABLE)
            ->orderBy('r.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
