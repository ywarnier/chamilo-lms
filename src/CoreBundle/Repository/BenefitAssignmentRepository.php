<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\BenefitAssignment;
use Chamilo\CoreBundle\Entity\Compensation;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BenefitAssignment>
 */
class BenefitAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BenefitAssignment::class);
    }

    /**
     * @return BenefitAssignment[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('ba')
            ->where('ba.user = :user')
            ->setParameter('user', $user->getId(), Types::INTEGER)
            ->orderBy('ba.assignmentDatetime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return BenefitAssignment[]
     */
    public function findByCompensation(Compensation $compensation): array
    {
        return $this->createQueryBuilder('ba')
            ->where('ba.compensation = :compensation')
            ->setParameter('compensation', $compensation->getId(), Types::INTEGER)
            ->orderBy('ba.assignmentDatetime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return BenefitAssignment[]
     */
    public function findFromDate(DateTime $date): array
    {
        return $this->createQueryBuilder('ba')
            ->where('ba.assignmentDatetime >= :date')
            ->setParameter('date', $date, Types::DATETIME_MUTABLE)
            ->orderBy('ba.assignmentDatetime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('ba')
            ->select('COUNT(ba)')
            ->where('ba.user = :user')
            ->setParameter('user', $user->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
