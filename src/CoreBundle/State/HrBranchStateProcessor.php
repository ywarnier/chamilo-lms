<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\BranchSync;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Write-side processor for HR branches.
 *
 * Pattern: Path D — decorates the API Platform persist processor.
 *
 * Side effects:
 * - Post: force branchType = 'hr' and inject the current AccessUrl
 *   (the BranchSync.url column is NOT NULL).
 * - Put: guard branchType against tampering — if a client submits another value,
 *   force it back to 'hr'.
 *
 * @implements ProcessorInterface<BranchSync, BranchSync|void>
 */
final class HrBranchStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        \assert($data instanceof BranchSync);

        if ($operation instanceof Post) {
            $data->setBranchType('hr');

            $accessUrl = $this->accessUrlHelper->getCurrent();

            if (null !== $accessUrl) {
                $data->setUrl($accessUrl);
            }
        } elseif ('hr' !== $data->getBranchType()) {
            $data->setBranchType('hr');
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
