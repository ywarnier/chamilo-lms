<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post as PostOp;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\BenefitAssignment;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Sets the current user as assignmentAuthor on POST if not already provided.
 *
 * @implements ProcessorInterface<BenefitAssignment, BenefitAssignment>
 */
final readonly class BenefitAssignmentStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private Security $security,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof PostOp && $data instanceof BenefitAssignment) {
            /** @var User|null $user */
            $user = $this->security->getUser();

            if ($user instanceof User) {
                $data->setAssignmentAuthor($user);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
