<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\JobOffer;
use Chamilo\CoreBundle\Entity\JobOfferApplication;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobOfferApplication>
 */
class JobOfferApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobOfferApplication::class);
    }

    public function findByJobOffer(JobOffer $jobOffer): array
    {
        return $this->createQueryBuilder('joa')
            ->where('joa.jobOffer = :offer')
            ->setParameter('offer', $jobOffer)
            ->orderBy('joa.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByJobOfferId(int $jobOfferId): array
    {
        return $this->createQueryBuilder('joa')
            ->join('joa.jobOffer', 'jo')
            ->where('jo.id = :id')
            ->setParameter('id', $jobOfferId)
            ->orderBy('joa.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByApplicant(User $user): array
    {
        return $this->createQueryBuilder('joa')
            ->where('joa.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('joa.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneByJobOfferAndApplicant(JobOffer $jobOffer, User $user): ?JobOfferApplication
    {
        return $this->createQueryBuilder('joa')
            ->where('joa.jobOffer = :offer')
            ->andWhere('joa.createdBy = :user')
            ->setParameter('offer', $jobOffer)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
