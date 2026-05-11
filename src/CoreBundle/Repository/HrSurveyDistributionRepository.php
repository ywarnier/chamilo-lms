<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\HrSurveyDistribution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HrSurveyDistribution>
 */
class HrSurveyDistributionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HrSurveyDistribution::class);
    }

    /**
     * @return HrSurveyDistribution[]
     */
    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category]);
    }

    /**
     * @return HrSurveyDistribution[]
     */
    public function findByCategoryAndUnit(string $category, int $unitId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.category = :category')
            ->andWhere('d.businessUnit = :unitId')
            ->setParameter('category', $category)
            ->setParameter('unitId', $unitId)
            ->getQuery()
            ->getResult()
        ;
    }
}
