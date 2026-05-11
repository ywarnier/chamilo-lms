<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\JobOffer;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Sets the current user as creator on POST.
 *
 * @implements ProcessorInterface<JobOffer, JobOffer>
 */
final readonly class JobOfferStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private UserHelper $userHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof JobOffer && $operation instanceof Post) {
            $user = $this->userHelper->getCurrent();

            if (null !== $user) {
                $data->setCreatedBy($user);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
