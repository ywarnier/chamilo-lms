<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\HrRoiUnitItem;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\FunctionInUnit;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserToFunctionInUnit;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProviderInterface<HrRoiUnitItem>
 */
final class HrRoiUnitStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExtraFieldValuesRepository $extraFieldValuesRepo,
    ) {}

    /**
     * @return HrRoiUnitItem[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $unitId = (int) ($context['filters']['unit'] ?? 0);

        if ($unitId <= 0) {
            return [];
        }

        $dateFilter = $context['filters']['accessStartDate'] ?? [];
        $dateAfter = $this->parseDate(\is_array($dateFilter) ? ($dateFilter['after'] ?? null) : null, false);
        $dateBefore = $this->parseDate(\is_array($dateFilter) ? ($dateFilter['before'] ?? null) : null, true);

        $users = $this->em->createQueryBuilder()
            ->select('DISTINCT u')
            ->from(User::class, 'u')
            ->join(UserToFunctionInUnit::class, 'utfiu', 'WITH', 'utfiu.user = u')
            ->join(FunctionInUnit::class, 'fiu', 'WITH', 'utfiu.functionInUnit = fiu')
            ->where('fiu.businessUnit = :unitId')
            ->setParameter('unitId', $unitId, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $items = [];

        /** @var User $user */
        foreach ($users as $user) {
            $userId = (int) $user->getId();

            $sessionQb = $this->em->createQueryBuilder()
                ->select('s')
                ->from(Session::class, 's')
                ->join(SessionRelUser::class, 'sru', 'WITH', 'sru.session = s')
                ->where('sru.user = :userId')
                ->andWhere('sru.relationType = :type')
                ->setParameter('userId', $userId, Types::INTEGER)
                ->setParameter('type', Session::STUDENT)
            ;

            if (null !== $dateAfter && null !== $dateBefore) {
                $sessionQb->andWhere('s.accessStartDate >= :start')
                    ->andWhere('s.accessStartDate <= :end')
                    ->setParameter('start', $dateAfter, Types::DATETIME_MUTABLE)
                    ->setParameter('end', $dateBefore, Types::DATETIME_MUTABLE)
                ;
            }

            $sessions = $sessionQb->getQuery()->getResult();

            $sessionsCount = \count($sessions);
            $totalInvestment = 0.0;

            /** @var Session $session */
            foreach ($sessions as $session) {
                $sessionId = (int) $session->getId();

                $learnerCount = (int) $this->em->createQueryBuilder()
                    ->select('COUNT(sru2.id)')
                    ->from(SessionRelUser::class, 'sru2')
                    ->where('sru2.session = :sessionId')
                    ->andWhere('sru2.relationType = :type')
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

                if ($learnerCount > 0) {
                    $totalInvestment += $cost / $learnerCount;
                }
            }

            $item = new HrRoiUnitItem();
            $item->userId = $userId;
            $item->userFullName = $user->getFullName();
            $item->sessionsCount = $sessionsCount;
            $item->totalInvestment = round($totalInvestment, 2);
            $item->averageCostPerSession = $sessionsCount > 0 ? round($totalInvestment / $sessionsCount, 2) : 0.0;

            $items[] = $item;
        }

        return $items;
    }

    private function parseDate(?string $value, bool $endOfDay): ?DateTime
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);

        if (false === $date) {
            return null;
        }

        $endOfDay ? $date->setTime(23, 59, 59) : $date->setTime(0, 0, 0);

        return $date;
    }
}
