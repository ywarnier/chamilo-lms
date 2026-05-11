<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\JobOfferApplication;
use Chamilo\CoreBundle\Repository\JobOfferApplicationRepository;
use Chamilo\CoreBundle\Repository\JobOfferQuizRepository;
use Chamilo\CoreBundle\Repository\TrackEExerciseRepository;
use Doctrine\ORM\EntityManagerInterface;

final class JobOfferScoreCalculatorHelper
{
    public function __construct(
        private readonly JobOfferQuizRepository $quizRepository,
        private readonly TrackEExerciseRepository $exerciseRepository,
        private readonly JobOfferApplicationRepository $applicationRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    public function computeForApplication(JobOfferApplication $application): ?float
    {
        $jobOffer = $application->getJobOffer();
        $offerQuizzes = $this->quizRepository->findByJobOffer($jobOffer);

        if ([] === $offerQuizzes) {
            return null;
        }

        $cQuizzes = array_map(fn ($oq) => $oq->getCquiz(), $offerQuizzes);
        $bestPerQuiz = $this->exerciseRepository->findBestPercentagePerQuiz(
            $application->getCreatedBy(),
            $cQuizzes,
        );

        if ([] === $bestPerQuiz) {
            return null;
        }

        $sum = array_sum(array_column($bestPerQuiz, 'bestPct'));
        $average = round($sum / \count($bestPerQuiz), 2);

        $application->setTotalScore($average);

        return $average;
    }

    /**
     * Computes scores for all applications of a job offer and flushes once.
     * Returns the number of applications updated.
     */
    public function computeForAllApplications(int $jobOfferId): int
    {
        $applications = $this->applicationRepository->findByJobOfferId($jobOfferId);
        $updated = 0;

        foreach ($applications as $application) {
            $score = $this->computeForApplication($application);
            if (null !== $score) {
                ++$updated;
            }
        }

        $this->em->flush();

        return $updated;
    }
}
