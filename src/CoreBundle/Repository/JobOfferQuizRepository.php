<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\JobOffer;
use Chamilo\CoreBundle\Entity\JobOfferQuiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobOfferQuiz>
 */
class JobOfferQuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobOfferQuiz::class);
    }

    /**
     * @return JobOfferQuiz[]
     */
    public function findByJobOffer(JobOffer $jobOffer): array
    {
        return $this->createQueryBuilder('joq')
            ->where('joq.jobOffer = :offer')
            ->setParameter('offer', $jobOffer)
            ->getQuery()
            ->getResult()
        ;
    }
}
