<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

// Chamilo HR extension

use Chamilo\CoreBundle\Repository\UserToFunctionInUnitRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'hr:send-position-expiry-reminders',
    description: 'Send HR reminder emails for positions expiring within a given number of days.',
)]
class SendPositionExpiryRemindersCommand extends Command
{
    public function __construct(
        private readonly UserToFunctionInUnitRepository $positionRepo,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Notify for positions expiring within N days', 30)
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'HR recipient email address')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days = (int) $input->getOption('days');
        $to = (string) $input->getOption('to');

        if ('' === $to) {
            $io->error('Provide --to <hr@example.com>');

            return Command::FAILURE;
        }

        $positions = $this->positionRepo->findExpiringPositions($days);

        if ([] === $positions) {
            $io->success('No positions expiring within '.$days.' days.');

            return Command::SUCCESS;
        }

        $lines = [];
        foreach ($positions as $pos) {
            $lines[] = \sprintf(
                '- %s → %s (%s) ends on %s',
                $pos->getUser()->getFullName(),
                $pos->getFunctionInUnit()->getTitle(),
                $pos->getFunctionInUnit()->getBusinessUnitTitle(),
                $pos->getEndDate()?->format('Y-m-d') ?? 'n/a',
            );
        }

        $body = 'The following positions expire within '.$days." days:\n\n".implode("\n", $lines);

        $email = (new Email())
            ->to($to)
            ->subject('HR: Positions expiring within '.$days.' days')
            ->text($body)
        ;

        $this->mailer->send($email);

        $io->success(\sprintf('Sent reminder for %d position(s) to %s.', \count($positions), $to));

        return Command::SUCCESS;
    }
}
