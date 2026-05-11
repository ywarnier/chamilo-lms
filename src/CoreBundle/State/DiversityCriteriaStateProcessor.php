<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\DiversityCriteria;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use DateTimeZone;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<DiversityCriteria, DiversityCriteria|void>
 */
final class DiversityCriteriaStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?DiversityCriteria
    {
        \assert($data instanceof DiversityCriteria);

        $user = $this->security->getUser();
        \assert($user instanceof User);

        $now = new DateTime('now', new DateTimeZone('UTC'));

        if ($operation instanceof Post) {
            $data->setCreatedBy($user);
            $data->setCreatedOn($now);
        }

        $data->setUpdatedBy($user);
        $data->setUpdatedOn($now);

        // Protect sensitive diversity data: extra field values must not be visible to other users.
        $data->getExtraField()->setVisibleToOthers(false);

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
