<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Hr;

// Chamilo HR extension

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\DiversityCriteria;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_HR")'))]
class HrDiversityGuidelinesController extends BaseController
{
    public function __construct(
        private readonly ExtraFieldValuesRepository $extraFieldValuesRepository,
        private readonly UserRepository $userRepository,
    ) {}

    #[Route('/hr/diversity-guidelines-data/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function stats(DiversityCriteria $criteria): JsonResponse
    {
        $extraField = $criteria->getExtraField();
        $options = $extraField->getOptions();
        $dataLabels = [];
        $dataValues = [];
        $totalValues = 0;

        /** @var ExtraFieldOptions $option */
        foreach ($options as $option) {
            $count = $this->extraFieldValuesRepository->countByFieldAndValue($extraField, (string) $option->getValue());

            $totalValues += $count;
            $dataLabels[] = $option->getDisplayText() ?? $option->getValue();
            $dataValues[] = $count;
        }

        $dataPercentages = [];
        foreach ($dataValues as $value) {
            $dataPercentages[] = $totalValues > 0
                ? (float) number_format($value / $totalValues * 100, 2)
                : 0.0;
        }

        $totalStaff = $this->userRepository->countActiveStaff();

        $filledCount = $this->extraFieldValuesRepository->countFilledUsersForField($extraField);

        $unfilledPercent = $totalStaff > 0
            ? (float) number_format(($totalStaff - $filledCount) / $totalStaff * 100, 1)
            : 0.0;

        return $this->json([
            'title' => $criteria->getTitle(),
            'description' => $criteria->getDescription(),
            'extraFieldTitle' => $extraField->getDisplayText() ?? $extraField->getVariable(),
            'labels' => $dataLabels,
            'values' => $dataValues,
            'percentages' => $dataPercentages,
            'staffTotal' => $totalStaff,
            'filledCount' => $filledCount,
            'unfilledPercent' => $unfilledPercent,
        ]);
    }
}
