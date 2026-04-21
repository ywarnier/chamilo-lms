<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\BenefitNotification;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\BenefitAssignmentRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use MessageManager;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HrBenefitController extends BaseController
{
    /**
     * Sends a notification message to the benefit recipient and stores the record.
     */
    #[Route('/hr/benefit-notify', name: 'hr_benefit_notify', methods: ['POST'])]
    #[IsGranted(
        new Expression('is_granted("ROLE_ADMIN) or is_granted("ROLE_HR")')
    )]
    public function benefitNotify(
        Request $request,
        BenefitAssignmentRepository $assignmentRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var array{assignmentId?: int, subject?: string, message?: string} $body */
        $body = json_decode($request->getContent(), true) ?? [];
        $assignmentId = (int) ($body['assignmentId'] ?? 0);
        $subject = trim((string) ($body['subject'] ?? ''));
        $message = trim((string) ($body['message'] ?? ''));

        if (!$assignmentId || '' === $subject || '' === $message) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        $assignment = $assignmentRepo->find($assignmentId);

        if (!$assignment) {
            return $this->json(['error' => 'Assignment not found'], 404);
        }

        /** @var User|null $sender */
        $sender = $this->getUser();

        if (!$sender instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $notification = new BenefitNotification();
        $notification
            ->setSubject($subject)
            ->setMessage($message)
            ->setSender($sender)
            ->setBenefitAssignment($assignment)
            ->setSentOn(new DateTime())
        ;

        $em->persist($notification);
        $em->flush();

        MessageManager::send_message(
            $assignment->getUser()->getId(),
            $subject,
            $message,
            [],
            [],
            0,
            0,
            0,
            0,
            $sender->getId(),
            true
        );

        return $this->json(['success' => true]);
    }
}
