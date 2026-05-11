<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\Compensation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Compensation>
 */
class CompensationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Compensation::class);
    }

    /**
     * @return Compensation[]
     */
    public function findAllOrderedByTitle(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function countAssignments(Compensation $compensation): int
    {
        return (int) $this->getEntityManager()
            ->createQuery(
                'SELECT COUNT(ba) FROM Chamilo\CoreBundle\Entity\BenefitAssignment ba
                 WHERE ba.compensation = :compensation'
            )
            ->setParameter('compensation', $compensation->getId(), Types::INTEGER)
            ->getSingleScalarResult()
        ;
    }
}
