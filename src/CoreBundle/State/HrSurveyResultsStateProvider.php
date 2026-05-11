<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\HrSurveyResultsItem;
use Chamilo\CoreBundle\Entity\FunctionInUnit;
use Chamilo\CoreBundle\Entity\HrSurveyDistribution;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserToFunctionInUnit;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<HrSurveyResultsItem>
 */
final class HrSurveyResultsStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?HrSurveyResultsItem
    {
        $distributionId = (int) ($uriVariables['id'] ?? 0);

        if ($distributionId <= 0) {
            return null;
        }

        $distribution = $this->em->getRepository(HrSurveyDistribution::class)->find($distributionId);

        if (!$distribution instanceof HrSurveyDistribution) {
            throw new NotFoundHttpException('Distribution not found.');
        }

        $survey = $distribution->getSurvey();
        $unit = $distribution->getBusinessUnit();
        $surveyId = (int) $survey->getIid();
        $unitId = (int) $unit->getId();

        $usernames = $this->fetchUnitUsernames($unitId);
        $totals = $this->fetchInvitationTotals($surveyId, $usernames);
        $questions = $this->buildQuestionResults($surveyId, $usernames);

        $item = new HrSurveyResultsItem();
        $item->distributionId = $distributionId;
        $item->surveyId = $surveyId;
        $item->surveyTitle = $survey->getTitle();
        $item->category = $distribution->getCategory();
        $item->businessUnitTitle = $unit->getTitle();
        $item->totalInvited = $totals['invited'];
        $item->totalAnswered = $totals['answered'];
        $item->questions = $questions;

        return $item;
    }

    /**
     * @return string[]
     */
    private function fetchUnitUsernames(int $unitId): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('DISTINCT u.username')
            ->from(User::class, 'u')
            ->join(UserToFunctionInUnit::class, 'utfiu', 'WITH', 'utfiu.user = u')
            ->join(FunctionInUnit::class, 'fiu', 'WITH', 'utfiu.functionInUnit = fiu')
            ->where('fiu.businessUnit = :unitId')
            ->setParameter('unitId', $unitId, Types::INTEGER)
            ->getQuery()
            ->getScalarResult()
        ;

        return array_map(static fn (array $row): string => (string) $row['username'], $rows);
    }

    /**
     * @param string[] $usernames
     *
     * @return array{invited: int, answered: int}
     */
    private function fetchInvitationTotals(int $surveyId, array $usernames): array
    {
        if ([] === $usernames) {
            return ['invited' => 0, 'answered' => 0];
        }

        $invited = (int) $this->em->createQueryBuilder()
            ->select('COUNT(DISTINCT i.iid)')
            ->from(CSurveyInvitation::class, 'i')
            ->join('i.user', 'u')
            ->where('i.survey = :surveyId')
            ->andWhere('u.username IN (:usernames)')
            ->setParameter('surveyId', $surveyId, Types::INTEGER)
            ->setParameter('usernames', $usernames, ArrayParameterType::STRING)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $answered = (int) $this->em->createQueryBuilder()
            ->select('COUNT(DISTINCT i.iid)')
            ->from(CSurveyInvitation::class, 'i')
            ->join('i.user', 'u')
            ->where('i.survey = :surveyId')
            ->andWhere('i.answered = 1')
            ->andWhere('u.username IN (:usernames)')
            ->setParameter('surveyId', $surveyId, Types::INTEGER)
            ->setParameter('usernames', $usernames, ArrayParameterType::STRING)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return ['invited' => $invited, 'answered' => $answered];
    }

    /**
     * @param string[] $usernames
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildQuestionResults(int $surveyId, array $usernames): array
    {
        $questions = $this->em->createQueryBuilder()
            ->select('q')
            ->from(CSurveyQuestion::class, 'q')
            ->where('q.survey = :surveyId')
            ->orderBy('q.sort', 'ASC')
            ->setParameter('surveyId', $surveyId, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $result = [];

        /** @var CSurveyQuestion $question */
        foreach ($questions as $question) {
            $questionId = (int) $question->getIid();

            $options = [];
            $optionRows = $this->em->createQueryBuilder()
                ->select('o.iid AS iid', 'o.optionText AS text', 'o.value AS value')
                ->from(CSurveyQuestionOption::class, 'o')
                ->where('o.question = :questionId')
                ->orderBy('o.sort', 'ASC')
                ->setParameter('questionId', $questionId, Types::INTEGER)
                ->getQuery()
                ->getArrayResult()
            ;

            foreach ($optionRows as $row) {
                $options[] = [
                    'optionId' => (int) $row['iid'],
                    'text' => (string) $row['text'],
                    'value' => (int) $row['value'],
                ];
            }

            $responses = [];
            $totalResponses = 0;

            if ([] !== $usernames) {
                $answerRows = $this->em->createQueryBuilder()
                    ->select('a.optionId AS option_id', 'COUNT(a.iid) AS cnt')
                    ->from(CSurveyAnswer::class, 'a')
                    ->where('a.question = :questionId')
                    ->andWhere('a.user IN (:usernames)')
                    ->groupBy('a.optionId')
                    ->setParameter('questionId', $questionId, Types::INTEGER)
                    ->setParameter('usernames', $usernames, ArrayParameterType::STRING)
                    ->getQuery()
                    ->getArrayResult()
                ;

                foreach ($answerRows as $row) {
                    $count = (int) $row['cnt'];
                    $responses[] = [
                        'optionId' => (string) $row['option_id'],
                        'count' => $count,
                    ];
                    $totalResponses += $count;
                }
            }

            $result[] = [
                'questionId' => $questionId,
                'title' => $question->getSurveyQuestion(),
                'type' => $question->getType(),
                'sort' => $question->getSort(),
                'options' => $options,
                'responses' => $responses,
                'totalResponses' => $totalResponses,
            ];
        }

        return $result;
    }
}
