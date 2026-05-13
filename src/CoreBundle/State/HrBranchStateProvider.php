<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\BranchSync;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Read-side provider for HR branches.
 *
 * - Collection: returns BranchSync rows where branchType = 'hr', sorted by title ASC.
 *   Applies API Platform FilterExtension (so the existing SearchFilter on `title` keeps
 *   working on /api/branches) and PaginationExtension (so `?page=`, `?itemsPerPage=`
 *   and `?pagination=false` are handled automatically and the response carries the
 *   Hydra pagination metadata).
 * - Item (Put/Delete): returns the entity only if it exists AND its branchType is 'hr';
 *   otherwise returns null so API Platform answers 404.
 *
 * @implements ProviderInterface<BranchSync>
 */
final class HrBranchStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FilterExtension $filterExtension,
        private readonly PaginationExtension $paginationExtension,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $qb = $this->em->createQueryBuilder()
                ->select('b', 'gz')
                ->from(BranchSync::class, 'b')
                ->leftJoin('b.geographicZone', 'gz')
                ->where('b.branchType = :type')
                ->setParameter('type', 'hr')
                ->orderBy('b.title', 'ASC')
            ;

            $queryNameGenerator = new QueryNameGenerator();

            $this->filterExtension->applyToCollection(
                $qb,
                $queryNameGenerator,
                BranchSync::class,
                $operation,
                $context,
            );

            $this->paginationExtension->applyToCollection(
                $qb,
                $queryNameGenerator,
                BranchSync::class,
                $operation,
                $context,
            );

            if ($this->paginationExtension->supportsResult(BranchSync::class, $operation, $context)) {
                return $this->paginationExtension->getResult($qb, BranchSync::class, $operation, $context);
            }

            return $qb->getQuery()->getResult();
        }

        $id = $uriVariables['id'] ?? null;

        if (null === $id) {
            return null;
        }

        $branch = $this->em->getRepository(BranchSync::class)->find($id);

        if (null === $branch || 'hr' !== $branch->getBranchType()) {
            return null;
        }

        return $branch;
    }
}
