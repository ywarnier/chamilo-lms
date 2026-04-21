<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Compensation;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Sets the current user as author on POST and updates timestamps.
 *
 * @implements ProcessorInterface<Compensation, Compensation>
 */
final readonly class CompensationStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private UserHelper $userHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Compensation) {
            $user = $this->userHelper->getCurrent();

            if ($operation instanceof Post && $user) {
                $data->setAuthor($user);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
