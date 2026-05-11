<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\FunctionInUnit;
use Chamilo\CoreBundle\Entity\HrSurveyDistribution;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserToFunctionInUnit;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Persists the HrSurveyDistribution and dispatches a CSurveyInvitation + in-platform
 * notification to every member of the target BusinessUnit.
 *
 * Pattern: Path D (state processor decorating the persist processor). The default
 * `api_platform.doctrine.orm.state.persist_processor` is invoked first so the entity
 * gets its auto-generated ID and `createdAt` populated; the side effect runs after.
 *
 * Idempotency: `userAlreadyInvited()` skips users who already have ANY CSurveyInvitation
 * for the same survey (regardless of `answered` status). Re-distributing the same
 * (survey, unit, category) tuple therefore only invites members added to the unit since
 * the previous distribution — never resends to already-invited users.
 *
 * Notification body — known limitation: the in-platform message tells users to "go to
 * your pending surveys" but does NOT include a direct URL to the legacy `fillsurvey.php`.
 * Reason: legacy `public/main/survey/fillsurvey.php` requires both `?course=<courseCode>`
 * and `?invitationcode=<code>` to render. HR-distributed surveys are platform-wide and
 * have no canonical course context, so we cannot synthesise a working URL without
 * picking an arbitrary course (which would mis-scope the survey resource link).
 * Users navigate to the survey via Chamilo's own "pending surveys" listing in their
 * profile. When the survey-taking form is migrated from legacy to Vue/API in a future
 * iteration, swap the message body for a direct deep link.
 *
 * @implements ProcessorInterface<HrSurveyDistribution, HrSurveyDistribution|void>
 */
final class HrSurveyDistributionProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly EntityManagerInterface $em,
        private readonly MessageHelper $messageHelper,
        private readonly TranslatorInterface $translator,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$operation instanceof Post) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        \assert($data instanceof HrSurveyDistribution);

        $persisted = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        \assert($persisted instanceof HrSurveyDistribution);

        $this->dispatchInvitations($persisted);

        return $persisted;
    }

    private function dispatchInvitations(HrSurveyDistribution $distribution): void
    {
        $survey = $distribution->getSurvey();
        $unit = $distribution->getBusinessUnit();

        if (null === $survey || null === $unit) {
            return;
        }

        $unitId = (int) $unit->getId();

        $users = $this->em->createQueryBuilder()
            ->select('DISTINCT u')
            ->from(User::class, 'u')
            ->join(UserToFunctionInUnit::class, 'utfiu', 'WITH', 'utfiu.user = u')
            ->join(FunctionInUnit::class, 'fiu', 'WITH', 'utfiu.functionInUnit = fiu')
            ->where('fiu.businessUnit = :unitId')
            ->setParameter('unitId', $unitId, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $subject = $this->translator->trans('Survey invitation: %title%', [
            '%title%' => $survey->getTitle(),
        ]);
        $body = $this->translator->trans(
            'You have been invited to fill in the survey "%title%". Please go to your pending surveys to complete it.',
            ['%title%' => $survey->getTitle()],
        );

        /** @var User $user */
        foreach ($users as $user) {
            if ($this->userAlreadyInvited($survey, $user)) {
                continue;
            }

            $invitation = (new CSurveyInvitation())
                ->setSurvey($survey)
                ->setUser($user)
                ->setInvitationCode(bin2hex(random_bytes(16)))
            ;

            $this->em->persist($invitation);

            $this->messageHelper->sendMessageSimple(
                (int) $user->getId(),
                $subject,
                $body,
            );
        }

        $this->em->flush();
    }

    private function userAlreadyInvited(CSurvey $survey, User $user): bool
    {
        $count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(i.iid)')
            ->from(CSurveyInvitation::class, 'i')
            ->where('i.survey = :survey')
            ->andWhere('i.user = :user')
            ->setParameter('survey', $survey->getIid(), Types::INTEGER)
            ->setParameter('user', $user->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }
}
