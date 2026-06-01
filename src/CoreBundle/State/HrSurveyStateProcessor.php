<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CSurvey;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * State processor for HR-managed CSurvey records (POST/PUT/DELETE).
 *
 * On POST: sets the parentResourceNode to the creating user's own ResourceNode
 * so the survey can exist without a course context.  Auto-generates a unique
 * `code` when the client does not supply one.
 *
 * On DELETE: delegates straight to the Doctrine remove processor.
 *
 * @implements ProcessorInterface<CSurvey, CSurvey|void>
 */
class HrSurveyStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private readonly ProcessorInterface $removeProcessor,
        private readonly Security $security,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof Delete) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        /** @var CSurvey $data */
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $userNode = $user->getResourceNode();
            if (null !== $userNode && !$data->hasParentResourceNode()) {
                $data->setParentResourceNode($userNode->getId());
            }
        }

        if (empty($data->getCode())) {
            // Generate a compact unique code that fits in the 40-char column.
            $data->setCode('HR'.strtoupper(substr(uniqid('', true), -12)));
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
