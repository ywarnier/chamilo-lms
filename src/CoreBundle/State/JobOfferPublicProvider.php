<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\JobOffer;
use Chamilo\CoreBundle\Repository\JobOfferRepository;

/**
 * @template-implements ProviderInterface<JobOffer>
 */
final class JobOfferPublicProvider implements ProviderInterface
{
    public function __construct(
        private readonly JobOfferRepository $repository,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->repository->findPublicActive();
    }
}
