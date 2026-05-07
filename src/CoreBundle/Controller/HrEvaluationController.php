<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\PerformanceAppraisal;
use Chamilo\CoreBundle\Entity\PerformanceAppraisalItem;
use Chamilo\CoreBundle\Entity\PerformanceAppraisalItemComment;
use Chamilo\CoreBundle\Entity\PerformanceAppraisalReminder;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\PerformanceAppraisalRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use MessageManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/hr/evaluations')]
class HrEvaluationController extends BaseController
{
    /**
     * Creates reminders for all 9 offsets when a new PerformanceAppraisal is saved via the API.
     * Called by the frontend after a successful POST to /api/performance_appraisals.
     */
    #[Route('/{id}/schedule-reminders', name: 'hr_evaluation_schedule_reminders', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function scheduleReminders(
        int $id,
        PerformanceAppraisalRepository $appraisalRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $appraisal = $appraisalRepo->find($id);

        if (!$appraisal) {
            return $this->json(['error' => 'Not found'], 404);
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_HR')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $existingReminders = $em->getRepository(PerformanceAppraisalReminder::class)
            ->findBy(['appraisal' => $appraisal])
        ;

        if (!empty($existingReminders)) {
            return $this->json(['message' => 'Reminders already scheduled']);
        }

        $baseDate = $appraisal->getScheduledAt();

        foreach (PerformanceAppraisalReminder::OFFSETS as $offsetDays) {
            $reminderDate = (clone $baseDate)->modify($offsetDays.'days');
            $reminder = new PerformanceAppraisalReminder();
            $reminder
                ->setAppraisal($appraisal)
                ->setScheduledAt($reminderDate)
                ->setOffsetDays($offsetDays)
                ->setIsReport(false)
            ;
            $em->persist($reminder);
        }

        // +14d report reminder for HR admins if appraisal still incomplete.
        $reportDate = (clone $baseDate)->modify('+14 days');
        $reportReminder = new PerformanceAppraisalReminder();
        $reportReminder
            ->setAppraisal($appraisal)
            ->setScheduledAt($reportDate)
            ->setOffsetDays(14)
            ->setIsReport(true)
        ;
        $em->persist($reportReminder);

        $em->flush();

        return $this->json(['message' => 'Reminders scheduled', 'count' => \count(PerformanceAppraisalReminder::OFFSETS) + 1]);
    }

    /**
     * Save evaluator's scores and comments as a draft (no status change).
     */
    #[Route('/{id}/save-draft', name: 'hr_evaluation_save_draft', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function saveDraft(
        int $id,
        Request $request,
        PerformanceAppraisalRepository $appraisalRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $appraisal = $appraisalRepo->find($id);

        if (!$appraisal) {
            return $this->json(['error' => 'Not found'], 404);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $isEvaluator = $appraisal->getEvaluatorUser()->getId() === $currentUser->getId()
            || $this->isGranted('ROLE_HR')
            || $this->isGranted('ROLE_ADMIN');
        $isEvaluated = $appraisal->getEvaluatedUser()->getId() === $currentUser->getId();

        if (!$isEvaluator && !$isEvaluated) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        /** @var array{items?: array<int, array{score?: int, responsibleComment?: string, collaboratorComment?: string}>} $body */
        $body = json_decode($request->getContent(), true) ?? [];
        $itemData = $body['items'] ?? [];

        $this->applyItemData($appraisal, $itemData, $isEvaluator, $isEvaluated);
        $appraisal->calcTotals();

        $em->flush();

        return $this->json(['message' => 'Draft saved', 'totalScore' => $appraisal->getTotalScore()]);
    }

    /**
     * Evaluator sends the evaluation to the evaluatee (STATUS_SCHEDULED → STATUS_DONE).
     */
    #[Route('/{id}/send-to-evaluatee', name: 'hr_evaluation_send_to_evaluatee', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function sendToEvaluatee(
        int $id,
        Request $request,
        PerformanceAppraisalRepository $appraisalRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $appraisal = $appraisalRepo->find($id);

        if (!$appraisal) {
            return $this->json(['error' => 'Not found'], 404);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $isEvaluator = $appraisal->getEvaluatorUser()->getId() === $currentUser->getId()
            || $this->isGranted('ROLE_HR')
            || $this->isGranted('ROLE_ADMIN');

        if (!$isEvaluator) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if (PerformanceAppraisal::STATUS_SCHEDULED !== $appraisal->getStatus()) {
            return $this->json(['error' => 'Appraisal is not in scheduled status'], 409);
        }

        /** @var array{items?: array<int, array{score?: int, responsibleComment?: string, collaboratorComment?: string}>} $body */
        $body = json_decode($request->getContent(), true) ?? [];
        $itemData = $body['items'] ?? [];

        $this->applyItemData($appraisal, $itemData, true, false);
        $appraisal->calcTotals();
        $appraisal->setStatus(PerformanceAppraisal::STATUS_DONE);

        $em->flush();

        $evaluatedUser = $appraisal->getEvaluatedUser();
        $link = $this->generateUrl('hr_vue_entrypoint', ['vueRouting' => 'my-evaluations']);
        $subject = 'Evaluation ready for your review';
        $message = 'Your evaluation has been completed by '.$appraisal->getEvaluatorUser()->getFullName().
            '. Please review and provide your feedback at: '.$link;

        MessageManager::send_message(
            $evaluatedUser->getId(),
            $subject,
            $message,
            [],
            [],
            0,
            0,
            0,
            0,
            $currentUser->getId(),
            true
        );

        return $this->json(['message' => 'Sent to evaluatee', 'status' => $appraisal->getStatus()]);
    }

    /**
     * Evaluatee submits their feedback (STATUS_DONE → STATUS_FEEDBACKED).
     */
    #[Route('/{id}/submit-feedback', name: 'hr_evaluation_submit_feedback', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function submitFeedback(
        int $id,
        Request $request,
        PerformanceAppraisalRepository $appraisalRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $appraisal = $appraisalRepo->find($id);

        if (!$appraisal) {
            return $this->json(['error' => 'Not found'], 404);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        if ($appraisal->getEvaluatedUser()->getId() !== $currentUser->getId()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if (PerformanceAppraisal::STATUS_DONE !== $appraisal->getStatus()) {
            return $this->json(['error' => 'Appraisal is not in done status'], 409);
        }

        /** @var array{items?: array<int, array{collaboratorComment?: string}>} $body */
        $body = json_decode($request->getContent(), true) ?? [];
        $itemData = $body['items'] ?? [];

        foreach ($appraisal->getItems() as $item) {
            $itemId = $item->getId();

            if (null === $itemId || !isset($itemData[$itemId])) {
                continue;
            }

            $data = $itemData[$itemId];

            if (isset($data['collaboratorComment'])) {
                $item->setCollaboratorComment((string) $data['collaboratorComment']);
            }
        }

        $appraisal->setStatus(PerformanceAppraisal::STATUS_FEEDBACKED);

        $em->flush();

        return $this->json(['message' => 'Feedback submitted', 'status' => $appraisal->getStatus()]);
    }

    /**
     * Evaluator closes the appraisal (STATUS_FEEDBACKED → STATUS_CLOSED).
     */
    #[Route('/{id}/close', name: 'hr_evaluation_close', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function close(
        int $id,
        PerformanceAppraisalRepository $appraisalRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $appraisal = $appraisalRepo->find($id);

        if (!$appraisal) {
            return $this->json(['error' => 'Not found'], 404);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $isEvaluator = $appraisal->getEvaluatorUser()->getId() === $currentUser->getId()
            || $this->isGranted('ROLE_HR')
            || $this->isGranted('ROLE_ADMIN');

        if (!$isEvaluator) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if (PerformanceAppraisal::STATUS_FEEDBACKED !== $appraisal->getStatus()) {
            return $this->json(['error' => 'Appraisal is not in feedbacked status'], 409);
        }

        $appraisal->setStatus(PerformanceAppraisal::STATUS_CLOSED);

        $em->flush();

        return $this->json(['message' => 'Appraisal closed', 'status' => $appraisal->getStatus()]);
    }

    /**
     * Add a comment on a specific appraisal item (available to evaluator and evaluatee).
     */
    #[Route('/items/{itemId}/comments', name: 'hr_evaluation_add_comment', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addComment(
        int $itemId,
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse {
        $item = $em->find(PerformanceAppraisalItem::class, $itemId);

        if (!$item) {
            return $this->json(['error' => 'Not found'], 404);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $appraisal = $item->getAppraisal();
        $isAllowed = $appraisal->getEvaluatorUser()->getId() === $currentUser->getId()
            || $appraisal->getEvaluatedUser()->getId() === $currentUser->getId()
            || $this->isGranted('ROLE_HR')
            || $this->isGranted('ROLE_ADMIN');

        if (!$isAllowed) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        /** @var array{comment?: string} $body */
        $body = json_decode($request->getContent(), true) ?? [];
        $commentText = trim((string) ($body['comment'] ?? ''));

        if ('' === $commentText) {
            return $this->json(['error' => 'Comment cannot be empty'], 422);
        }

        $comment = new PerformanceAppraisalItemComment();
        $comment
            ->setComment($commentText)
            ->setSender($currentUser)
            ->setCreatedOn(new DateTime())
        ;

        $item->addComment($comment);
        $em->flush();

        return $this->json([
            'id' => $comment->getId(),
            'comment' => $comment->getComment(),
            'sender' => $comment->getSender()->getFullName(),
            'createdOn' => $comment->getCreatedOn()->format('Y-m-d H:i'),
        ]);
    }

    /**
     * Save action plan items linked to an evaluation (evaluator only).
     */
    #[Route('/{id}/action-plan', name: 'hr_evaluation_action_plan', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function saveActionPlan(
        int $id,
        Request $request,
        PerformanceAppraisalRepository $appraisalRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $appraisal = $appraisalRepo->find($id);

        if (!$appraisal) {
            return $this->json(['error' => 'Not found'], 404);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $isEvaluator = $appraisal->getEvaluatorUser()->getId() === $currentUser->getId()
            || $this->isGranted('ROLE_HR')
            || $this->isGranted('ROLE_ADMIN');

        if (!$isEvaluator) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        /** @var array{scheduledAt?: string, items?: array<array{type: string, ref: int, percentage: int}>} $body */
        $body = json_decode($request->getContent(), true) ?? [];

        $actionPlan = $appraisal->getActionPlan();

        if (null === $actionPlan) {
            $actionPlan = new PerformanceAppraisal();
            $actionPlan
                ->setTemplate($appraisal->getTemplate())
                ->setStage($appraisal->getStage())
                ->setEvaluatorUser($appraisal->getEvaluatorUser())
                ->setEvaluatedUser($appraisal->getEvaluatedUser())
                ->setStatus(PerformanceAppraisal::STATUS_SCHEDULED)
            ;

            if (!empty($body['scheduledAt'])) {
                $actionPlan->setScheduledAt(new DateTime($body['scheduledAt']));
            } else {
                $actionPlan->setScheduledAt(new DateTime());
            }

            $em->persist($actionPlan);
        }

        $actionPlan->removeAllItems();

        foreach ($body['items'] ?? [] as $itemData) {
            $type = (string) ($itemData['type'] ?? '');
            $ref = (int) ($itemData['ref'] ?? 0);
            $percentage = (int) ($itemData['percentage'] ?? 0);

            if (!\in_array($type, [PerformanceAppraisalItem::TYPE_SKILL, PerformanceAppraisalItem::TYPE_ACTIVITY, PerformanceAppraisalItem::TYPE_OBJECTIVE], true) || $ref <= 0) {
                continue;
            }

            $item = new PerformanceAppraisalItem();
            $item
                ->setType($type)
                ->setRef($ref)
                ->setPercentage($percentage)
            ;
            $actionPlan->addItem($item);
        }

        $em->flush();

        $appraisal->setActionPlan($actionPlan);
        $em->flush();

        return $this->json(['message' => 'Action plan saved', 'actionPlanId' => $actionPlan->getId()]);
    }

    /**
     * Data endpoint — history scores for chart.
     */
    #[Route('/{id}/history-data', name: 'hr_evaluation_history_data', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function historyData(
        int $id,
        PerformanceAppraisalRepository $appraisalRepo,
    ): JsonResponse {
        $appraisal = $appraisalRepo->find($id);

        if (!$appraisal) {
            return $this->json(['error' => 'Not found'], 404);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $isAllowed = $appraisal->getEvaluatorUser()->getId() === $currentUser->getId()
            || $appraisal->getEvaluatedUser()->getId() === $currentUser->getId()
            || $this->isGranted('ROLE_HR')
            || $this->isGranted('ROLE_ADMIN');

        if (!$isAllowed) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $history = $appraisalRepo->findHistoryForEvaluatedUser($appraisal->getEvaluatedUser());

        $labels = [];
        $scores = [];

        foreach ($history as $past) {
            $labels[] = $past->getScheduledAt()->format('Y-m-d');
            $scores[] = $past->getTotalScore() ?? 0;
        }

        return $this->json(['labels' => $labels, 'scores' => $scores]);
    }

    /**
     * @param array<int, array{score?: int, responsibleComment?: string, collaboratorComment?: string}> $itemData
     */
    private function applyItemData(
        PerformanceAppraisal $appraisal,
        array $itemData,
        bool $isEvaluator,
        bool $isEvaluated,
    ): void {
        foreach ($appraisal->getItems() as $item) {
            $itemId = $item->getId();

            if (null === $itemId || !isset($itemData[$itemId])) {
                continue;
            }

            $data = $itemData[$itemId];

            if ($isEvaluator) {
                if (isset($data['score'])) {
                    $item->setScore((int) $data['score']);
                }

                if (isset($data['responsibleComment'])) {
                    $item->setResponsibleComment((string) $data['responsibleComment']);
                }
            }

            if ($isEvaluated && isset($data['collaboratorComment'])) {
                $item->setCollaboratorComment((string) $data['collaboratorComment']);
            }
        }
    }
}
