<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\BenefitAssignment;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\BenefitAssignmentRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @template-implements ProviderInterface<BenefitAssignment>
 */
final class MyBenefitsStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly BenefitAssignmentRepository $repository,
        private readonly UserHelper $userHelper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->userHelper->getCurrent();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        return $this->repository->findByUser($user);
    }
}
