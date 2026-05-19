<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\JobOfferApplication;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Loads a {@see JobOfferApplication} by its `{id}` URI variable.
 * Throws 404 when the record does not exist, preserving the legacy controller behaviour
 * for POST item operations such as `/job_offer_applications/{id}/withdraw`.
 *
 * @implements ProviderInterface<JobOfferApplication>
 */
final readonly class JobOfferApplicationItemProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private ProviderInterface $itemProvider,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): JobOfferApplication
    {
        $application = $this->itemProvider->provide($operation, $uriVariables, $context);

        if (!$application instanceof JobOfferApplication) {
            throw new NotFoundHttpException('Job offer application not found.');
        }

        return $application;
    }
}
