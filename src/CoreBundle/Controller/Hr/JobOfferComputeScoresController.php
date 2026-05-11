<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Hr;

// Chamilo HR extension

use Chamilo\CoreBundle\Helpers\JobOfferScoreCalculatorHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_HR")'))]
class JobOfferComputeScoresController extends AbstractController
{
    public function __construct(
        private readonly JobOfferScoreCalculatorHelper $calculatorHelper,
    ) {}

    #[Route(
        '/api/job_offers/{offerId}/compute_scores',
        name: 'hr_job_offer_compute_scores',
        methods: ['POST'],
    )]
    public function __invoke(int $offerId): JsonResponse
    {
        $updated = $this->calculatorHelper->computeForAllApplications($offerId);

        return $this->json(['updated' => $updated]);
    }
}
