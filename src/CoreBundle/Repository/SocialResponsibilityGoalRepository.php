<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\SocialResponsibilityGoal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SocialResponsibilityGoal>
 */
class SocialResponsibilityGoalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SocialResponsibilityGoal::class);
    }

    /**
     * @return SocialResponsibilityGoal[]
     */
    public function findPublished(string $language): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.isPublished = true')
            ->andWhere('g.language = :language')
            ->setParameter('language', $language)
            ->orderBy('g.sdgNumber', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
