<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Hr;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\JobOfferApplication;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class JobOfferApplicationWithdrawController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route(
        '/api/job_offer_applications/{id}/withdraw',
        name: 'hr_job_offer_application_withdraw',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function __invoke(JobOfferApplication $application): JsonResponse
    {
        if ($application->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (null !== $application->getWithdrawnAt()) {
            return $this->json(['error' => 'Application already withdrawn.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $application->setWithdrawnAt(new DateTimeImmutable());
        $this->em->flush();

        return $this->json(['message' => 'Application withdrawn.']);
    }
}
