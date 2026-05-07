<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\PerformanceAppraisal;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\PerformanceAppraisalRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns evaluations for the current user, filtered by role query param.
 * role=evaluatee → evaluations where the user is being evaluated.
 * role=evaluator → evaluations where the user conducts the evaluation.
 *
 * @template-implements ProviderInterface<PerformanceAppraisal>
 */
final class MyEvaluationsStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly PerformanceAppraisalRepository $repository,
        private readonly UserHelper $userHelper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->userHelper->getCurrent();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $role = (string) ($context['request']?->query->get('role') ?? 'evaluatee');

        if ('evaluator' === $role) {
            return $this->repository->findByEvaluatorUser($user);
        }

        return $this->repository->findByEvaluatedUser($user);
    }
}
