<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\JobOfferApplication;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<JobOfferApplication, JobOfferApplication>
 */
final readonly class JobOfferApplicationWithdrawProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        \assert($data instanceof JobOfferApplication);

        if (null !== $data->getWithdrawnAt()) {
            throw new UnprocessableEntityHttpException('Application already withdrawn.');
        }

        $data->setWithdrawnAt(new DateTimeImmutable());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
