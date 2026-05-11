<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\HrRoiPersonItem;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

/**
 * State provider for the HR ROI-by-person endpoint (`GET /api/hr_roi/person`).
 *
 * The operation lives on `SessionRelUser` (Path B canonical) so the class-level
 * #[ApiFilter] declarations on that entity work natively (SearchFilter on `user`,
 * DateFilter on `session.accessStartDate`). The relevant URL params for this endpoint are:
 *   - ?user=<userId>                    required, IRI or numeric ID
 *   - ?session.accessStartDate[after]=YYYY-MM-DD   optional
 *   - ?session.accessStartDate[before]=YYYY-MM-DD  optional
 *
 * NOTE on `relationType`: SessionRelUser also exposes `relationType` and `session` as
 * SearchFilter params at the class level. Both are inherited by this operation but the
 * provider hardcodes `WHERE sru.relationType = STUDENT` before applying FilterExtension,
 * so a client query like `?relationType=2` yields an empty result (two conflicting WHEREs
 * AND-combined). HR ROI is "investment per learner" — non-student subscriptions don't
 * carry a cost share, so this is intentional. The exposed filter is harmless cosmetic
 * leakage from the parent #[ApiResource] declaration.
 *
 * @implements ProviderInterface<HrRoiPersonItem>
 */
final class HrRoiPersonStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExtraFieldValuesRepository $extraFieldValuesRepo,
        private readonly FilterExtension $filterExtension,
    ) {}

    /**
     * @return HrRoiPersonItem[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        // Require the `user` filter to be present — empty result otherwise.
        if (empty($context['filters']['user'])) {
            return [];
        }

        $qb = $this->em->createQueryBuilder()
            ->select('sru', 's')
            ->from(SessionRelUser::class, 'sru')
            ->leftJoin('sru.session', 's')
            ->where('sru.relationType = :type')
            ->setParameter('type', Session::STUDENT)
            ->orderBy('s.accessStartDate', 'DESC')
        ;

        $this->filterExtension->applyToCollection(
            $qb,
            new QueryNameGenerator(),
            SessionRelUser::class,
            $operation,
            $context,
        );

        $relUsers = $qb->getQuery()->getResult();
        $items = [];

        /** @var SessionRelUser $sru */
        foreach ($relUsers as $sru) {
            $session = $sru->getSession();

            if (null === $session) {
                continue;
            }

            $sessionId = (int) $session->getId();

            $learnerCount = (int) $this->em->createQueryBuilder()
                ->select('COUNT(sru2.id)')
                ->from(SessionRelUser::class, 'sru2')
                ->where('sru2.session = :sessionId')
                ->andWhere('sru2.relationType = :type')
                ->setParameter('sessionId', $sessionId, Types::INTEGER)
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

            $item = new HrRoiPersonItem();
            $item->sessionId = $sessionId;
            $item->title = $session->getTitle();
            $item->accessStartDate = $session->getAccessStartDate()?->format('Y-m-d');
            $item->accessEndDate = $session->getAccessEndDate()?->format('Y-m-d');
            $item->learnerCount = $learnerCount;
            $item->cost = $cost;
            $item->userShare = $learnerCount > 0 ? round($cost / $learnerCount, 2) : null;

            $items[] = $item;
        }

        return $items;
    }
}
