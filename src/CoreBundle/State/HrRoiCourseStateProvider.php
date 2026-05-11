<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\HrRoiCourseItem;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProviderInterface<HrRoiCourseItem>
 */
final class HrRoiCourseStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExtraFieldValuesRepository $extraFieldValuesRepo,
        private readonly FilterExtension $filterExtension,
    ) {}

    /**
     * @return HrRoiCourseItem[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from(Session::class, 's')
            ->orderBy('s.accessStartDate', 'DESC')
        ;

        $queryNameGenerator = new QueryNameGenerator();
        $this->filterExtension->applyToCollection(
            $qb,
            $queryNameGenerator,
            Session::class,
            $operation,
            $context
        );

        $sessions = $qb->getQuery()->getResult();
        $items = [];

        /** @var Session $session */
        foreach ($sessions as $session) {
            $sessionId = (int) $session->getId();

            $learnerCount = (int) $this->em->createQueryBuilder()
                ->select('COUNT(sru.id)')
                ->from(SessionRelUser::class, 'sru')
                ->where('sru.session = :sessionId')
                ->andWhere('sru.relationType = :type')
                ->setParameter('sessionId', $session->getId(), Types::INTEGER)
                ->setParameter('type', Session::STUDENT)
                ->getQuery()
                ->getSingleScalarResult()
            ;

            $efv = $this->extraFieldValuesRepo->getValueByVariableAndItem(
                'cost',
                $sessionId,
                ExtraField::SESSION_FIELD_TYPE
            );
            $cost = (float) ($efv?->getFieldValue() ?? 0);

            $item = new HrRoiCourseItem();
            $item->sessionId = $sessionId;
            $item->title = $session->getTitle();
            $item->accessStartDate = $session->getAccessStartDate()?->format('Y-m-d');
            $item->accessEndDate = $session->getAccessEndDate()?->format('Y-m-d');
            $item->learnerCount = $learnerCount;
            $item->cost = $cost;
            $item->costPerLearner = $learnerCount > 0 ? round($cost / $learnerCount, 2) : null;

            $items[] = $item;
        }

        return $items;
    }
}
