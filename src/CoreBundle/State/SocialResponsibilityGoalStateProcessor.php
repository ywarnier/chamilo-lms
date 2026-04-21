<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\SocialResponsibilityGoal;
use Chamilo\CoreBundle\Entity\SocialResponsibilityGoalHistory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<SocialResponsibilityGoal, SocialResponsibilityGoal>
 */
final readonly class SocialResponsibilityGoalStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): SocialResponsibilityGoal
    {
        \assert($data instanceof SocialResponsibilityGoal);

        $now = new DateTimeImmutable();

        // Snapshot the previous state into history before overwriting
        if (null !== $data->getId()) {
            $history = new SocialResponsibilityGoalHistory();
            $history
                ->setGoal($data)
                ->setDescription($data->getDescription())
                ->setEnforcement($data->getEnforcement())
                ->setIsPublished($data->getIsPublished())
                ->setChangedAt($now)
                ->setChangedBy($this->security->getUser())
            ;
            $this->entityManager->persist($history);
        }

        $data
            ->setUpdatedAt($now)
            ->setUpdatedBy($this->security->getUser())
        ;

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
