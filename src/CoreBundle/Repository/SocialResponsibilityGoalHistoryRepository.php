<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\SocialResponsibilityGoal;
use Chamilo\CoreBundle\Entity\SocialResponsibilityGoalHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SocialResponsibilityGoalHistory>
 */
class SocialResponsibilityGoalHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SocialResponsibilityGoalHistory::class);
    }

    /**
     * @return SocialResponsibilityGoalHistory[]
     */
    public function findByGoal(SocialResponsibilityGoal $goal): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.goal = :goal')
            ->setParameter('goal', $goal)
            ->orderBy('h.changedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
