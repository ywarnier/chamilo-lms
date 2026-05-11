<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\JobOfferApplication;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\JobOfferApplicationRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @template-implements ProviderInterface<JobOfferApplication>
 */
final class JobOfferMyApplicationsProvider implements ProviderInterface
{
    public function __construct(
        private readonly JobOfferApplicationRepository $repository,
        private readonly UserHelper $userHelper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->userHelper->getCurrent();
        if (null === $user) {
            throw new AccessDeniedHttpException();
        }

        return $this->repository->findByApplicant($user);
    }
}
