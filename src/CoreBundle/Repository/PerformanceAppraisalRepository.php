<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\PerformanceAppraisal;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PerformanceAppraisal>
 */
class PerformanceAppraisalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerformanceAppraisal::class);
    }

    /**
     * @return PerformanceAppraisal[]
     */
    public function findByEvaluatedUser(User $user): array
    {
        return $this->createQueryBuilder('pa')
            ->where('pa.evaluatedUser = :user')
            ->setParameter('user', $user->getId(), Types::INTEGER)
            ->orderBy('pa.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PerformanceAppraisal[]
     */
    public function findByEvaluatorUser(User $user): array
    {
        return $this->createQueryBuilder('pa')
            ->where('pa.evaluatorUser = :user')
            ->setParameter('user', $user->getId(), Types::INTEGER)
            ->orderBy('pa.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PerformanceAppraisal[]
     */
    public function findHistoryForEvaluatedUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('pa')
            ->where('pa.evaluatedUser = :user')
            ->andWhere('pa.status = :status')
            ->setParameter('user', $user->getId(), Types::INTEGER)
            ->setParameter('status', PerformanceAppraisal::STATUS_CLOSED)
            ->orderBy('pa.scheduledAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }
}
