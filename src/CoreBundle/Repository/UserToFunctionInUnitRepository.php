<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\UserToFunctionInUnit;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserToFunctionInUnit>
 */
class UserToFunctionInUnitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserToFunctionInUnit::class);
    }

    /**
     * Find positions expiring within the given number of days.
     *
     * @return UserToFunctionInUnit[]
     */
    public function findExpiringPositions(int $days): array
    {
        $now = new DateTimeImmutable();
        $threshold = $now->modify('+'.$days.' days');

        return $this->createQueryBuilder('p')
            ->where('p.endDate IS NOT NULL')
            ->andWhere('p.endDate > :now')
            ->andWhere('p.endDate <= :threshold')
            ->setParameter('now', $now)
            ->setParameter('threshold', $threshold)
            ->orderBy('p.endDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
