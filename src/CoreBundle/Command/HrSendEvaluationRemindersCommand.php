<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\PerformanceAppraisal;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\PerformanceAppraisalReminderRepository;
use Doctrine\ORM\EntityManagerInterface;
use MessageManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'hr:send-evaluation-reminders',
    description: 'Sends scheduled evaluation reminder emails to evaluators and evaluatees.',
)]
class HrSendEvaluationRemindersCommand extends Command
{
    public function __construct(
        private readonly PerformanceAppraisalReminderRepository $reminderRepo,
        private readonly EntityManagerInterface $em,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $reminders = $this->reminderRepo->findDue();
        $sent = 0;

        foreach ($reminders as $reminder) {
            $appraisal = $reminder->getAppraisal();

            // Always mark as sent to avoid re-sending even if conditions changed.
            $reminder->setSent(true);

            if ($reminder->isReport()) {
                if (PerformanceAppraisal::STATUS_CLOSED !== $appraisal->getStatus()) {
                    $this->sendIncompleteReport($appraisal);
                    ++$sent;
                }

                continue;
            }

            if (PerformanceAppraisal::STATUS_CLOSED !== $appraisal->getStatus()) {
                $this->sendReminderEmail($appraisal, $reminder->getOffsetDays());
                ++$sent;
            }
        }

        $this->em->flush();

        $io->success(\sprintf('Processed %d due reminders, sent %d emails.', \count($reminders), $sent));

        return Command::SUCCESS;
    }

    private function sendReminderEmail(PerformanceAppraisal $appraisal, int $offsetDays): void
    {
        $evaluationLink = $this->urlGenerator->generate(
            'hr_vue_entrypoint',
            ['vueRouting' => 'evaluations/'.$appraisal->getId().'/execute'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $evaluatee = $appraisal->getEvaluatedUser();
        $evaluator = $appraisal->getEvaluatorUser();
        $scheduledDate = $appraisal->getScheduledAt()->format('Y-m-d');

        if ($offsetDays <= 0) {
            $when = 0 === $offsetDays
                ? 'today'
                : abs($offsetDays).' days from now';
            $subject = 'Upcoming evaluation reminder: '.$scheduledDate;
        } else {
            $when = $offsetDays.' days ago (overdue)';
            $subject = 'Overdue evaluation: '.$scheduledDate;
        }

        $message = 'An evaluation for '.$evaluatee->getFullName().
            ' is scheduled on '.$scheduledDate.' ('.$when.').'.
            ' Access the evaluation at: '.$evaluationLink;

        MessageManager::send_message(
            $evaluator->getId(),
            $subject,
            $message,
            [],
            [],
            0,
            0,
            0,
            0,
            0,
            true
        );
    }

    private function sendIncompleteReport(PerformanceAppraisal $appraisal): void
    {
        $evaluationLink = $this->urlGenerator->generate(
            'hr_vue_entrypoint',
            ['vueRouting' => 'evaluations/'.$appraisal->getId().'/execute'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $evaluatee = $appraisal->getEvaluatedUser();
        $scheduledDate = $appraisal->getScheduledAt()->format('Y-m-d');
        $subject = 'Incomplete evaluation report: '.$evaluatee->getFullName();
        $message = 'The evaluation for '.$evaluatee->getFullName().
            ' scheduled on '.$scheduledDate.
            ' is still incomplete (+14 days overdue). Status: '.PerformanceAppraisal::getStatusName($appraisal->getStatus()).
            '. Access it at: '.$evaluationLink;

        $hrUsers = $this->em->getRepository(User::class)->findByRole('ROLE_HR', '');

        foreach ($hrUsers as $hrUser) {
            MessageManager::send_message(
                $hrUser->getId(),
                $subject,
                $message,
                [],
                [],
                0,
                0,
                0,
                0,
                0,
                true
            );
        }
    }
}
