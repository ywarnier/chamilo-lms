<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\JobOffer;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobOffer>
 */
class JobOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobOffer::class);
    }

    /**
     * Returns public offers whose publication period covers today (or have no period set).
     *
     * @return JobOffer[]
     */
    public function findPublicActive(): array
    {
        $now = new DateTime();

        return $this->createQueryBuilder('jo')
            ->where('jo.isPublic = :public')
            ->andWhere('jo.publicationStartDate IS NULL OR jo.publicationStartDate <= :now')
            ->andWhere('jo.publicationEndDate IS NULL OR jo.publicationEndDate >= :now')
            ->setParameter('public', true)
            ->setParameter('now', $now)
            ->orderBy('jo.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
