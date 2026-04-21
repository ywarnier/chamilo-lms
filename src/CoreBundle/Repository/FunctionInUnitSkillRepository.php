<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\FunctionInUnitSkill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FunctionInUnitSkill>
 */
class FunctionInUnitSkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FunctionInUnitSkill::class);
    }
}
