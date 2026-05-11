<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\JobOfferApplication;
use Chamilo\CoreBundle\Helpers\UserHelper;
use DateTime;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * On POST: sets the current user as applicant.
 * On PUT: records the evaluator and the timestamp of the first evaluation.
 *
 * @implements ProcessorInterface<JobOfferApplication, JobOfferApplication>
 */
final readonly class JobOfferApplicationStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private UserHelper $userHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof JobOfferApplication) {
            $user = $this->userHelper->getCurrent();

            if ($operation instanceof Post && $user) {
                $data->setCreatedBy($user);
            }

            if ($operation instanceof Put && $user) {
                $data->setEvaluatedBy($user);

                if (null === $data->getFirstEvaluatedAt()) {
                    $data->setFirstEvaluatedAt(new DateTime());
                }
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
