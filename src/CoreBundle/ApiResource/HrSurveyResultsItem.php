<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

// Chamilo HR extension

use Symfony\Component\Serializer\Attribute\Groups;

final class HrSurveyResultsItem
{
    #[Groups(['hr_survey_results:read'])]
    public int $distributionId = 0;

    #[Groups(['hr_survey_results:read'])]
    public int $surveyId = 0;

    #[Groups(['hr_survey_results:read'])]
    public string $surveyTitle = '';

    #[Groups(['hr_survey_results:read'])]
    public string $category = '';

    #[Groups(['hr_survey_results:read'])]
    public string $businessUnitTitle = '';

    #[Groups(['hr_survey_results:read'])]
    public int $totalInvited = 0;

    #[Groups(['hr_survey_results:read'])]
    public int $totalAnswered = 0;

    /**
     * One entry per survey question, in display order.
     * Each entry: {
     *   questionId: int,
     *   title: string,
     *   type: string (yesno|score|multiplechoice|multipleresponse|dropdown|percentage|comment|open|...),
     *   options: [{ optionId: int, text: string, value: int }],
     *   responses: [{ optionId: string, count: int }],
     *   totalResponses: int,
     * }.
     *
     * `responses` groups CSurveyAnswer rows by their `option_id` text. For closed-choice
     * question types, `option_id` is the numeric `CSurveyQuestionOption::iid` cast to string —
     * cross-reference with `options[].optionId` to render labels. For open / comment types,
     * `option_id` IS the user's free-text answer.
     *
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['hr_survey_results:read'])]
    public array $questions = [];
}
