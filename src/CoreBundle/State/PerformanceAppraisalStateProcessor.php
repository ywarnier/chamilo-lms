<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post as PostOp;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\PerformanceAppraisal;
use Chamilo\CoreBundle\Entity\PerformanceAppraisalItem;

/**
 * Copies template items into the new appraisal on POST so the evaluation form
 * has rows to fill in from the start.
 *
 * @implements ProcessorInterface<PerformanceAppraisal, PerformanceAppraisal>
 */
final readonly class PerformanceAppraisalStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof PostOp && $data instanceof PerformanceAppraisal) {
            foreach ($data->getTemplate()->getItems() as $templateItem) {
                $item = (new PerformanceAppraisalItem())
                    ->setType($templateItem->getType())
                    ->setRef($templateItem->getRef())
                    ->setPercentage($templateItem->getPercentage())
                ;
                $data->addItem($item);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
