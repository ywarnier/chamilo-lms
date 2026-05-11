<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\MyCareerPlan;
use Chamilo\CoreBundle\Entity\BusinessUnit;
use Chamilo\CoreBundle\Entity\FunctionInUnit;
use Chamilo\CoreBundle\Entity\FunctionInUnitSkill;
use Chamilo\CoreBundle\Entity\Level;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\UserToFunctionInUnit;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<MyCareerPlan>
 */
final class MyCareerPlanStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserHelper $userHelper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): MyCareerPlan
    {
        $user = $this->userHelper->getCurrent();
        if (null === $user) {
            throw new AccessDeniedHttpException();
        }

        $result = new MyCareerPlan();

        /** @var UserToFunctionInUnit[] $assignments */
        $assignments = $this->em->createQueryBuilder()
            ->select('utfiu', 'fiu', 'bu', 'buParent')
            ->from(UserToFunctionInUnit::class, 'utfiu')
            ->join('utfiu.functionInUnit', 'fiu')
            ->join('fiu.businessUnit', 'bu')
            ->leftJoin('bu.parent', 'buParent')
            ->where('utfiu.user = :userId')
            ->setParameter('userId', $user->getId(), Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        if (empty($assignments)) {
            return $result;
        }

        $acquiredLevelBySkillId = $this->fetchAcquiredLevels($user->getId());
        $profileLevelCounts = $this->fetchProfileLevelCounts();

        $targetFunctionIds = [];
        $targetFunctions = [];

        foreach ($assignments as $assignment) {
            $fiu = $assignment->getFunctionInUnit();
            $bu = $fiu->getBusinessUnit();

            $result->currentPositions[] = [
                'id' => $fiu->getId(),
                'title' => $fiu->getTitle(),
                'businessUnitTitle' => $bu->getTitle(),
                'parentBusinessUnitTitle' => $bu->getParent()?->getTitle(),
            ];

            $parent = $bu->getParent();
            if (null === $parent) {
                continue;
            }

            // FunctionInUnit under the parent BusinessUnit
            foreach ($this->findFunctionsInUnit($parent) as $targetFiu) {
                $tid = (int) $targetFiu->getId();
                if ($tid !== (int) $fiu->getId() && !isset($targetFunctionIds[$tid])) {
                    $targetFunctionIds[$tid] = true;
                    $targetFunctions[] = [$targetFiu, $parent];
                }
            }

            // Siblings: other BusinessUnits under the same parent
            /** @var BusinessUnit[] $siblings */
            $siblings = $this->em->createQueryBuilder()
                ->select('sibling')
                ->from(BusinessUnit::class, 'sibling')
                ->where('sibling.parent = :parentId')
                ->andWhere('sibling.id != :currentId')
                ->setParameter('parentId', $parent->getId(), Types::INTEGER)
                ->setParameter('currentId', $bu->getId(), Types::INTEGER)
                ->getQuery()
                ->getResult()
            ;

            foreach ($siblings as $sibling) {
                foreach ($this->findFunctionsInUnit($sibling) as $targetFiu) {
                    $tid = (int) $targetFiu->getId();
                    if (!isset($targetFunctionIds[$tid])) {
                        $targetFunctionIds[$tid] = true;
                        $targetFunctions[] = [$targetFiu, $sibling];
                    }
                }
            }
        }

        foreach ($targetFunctions as [$targetFiu, $targetBu]) {
            $result->targets[] = [
                'id' => $targetFiu->getId(),
                'title' => $targetFiu->getTitle(),
                'businessUnitTitle' => $targetBu->getTitle(),
                'skills' => $this->buildSkillGaps($targetFiu, $acquiredLevelBySkillId, $profileLevelCounts),
            ];
        }

        return $result;
    }

    /**
     * @return FunctionInUnit[]
     */
    private function findFunctionsInUnit(BusinessUnit $bu): array
    {
        return $this->em->createQueryBuilder()
            ->select('fiu', 'skills', 'skill', 'lvl')
            ->from(FunctionInUnit::class, 'fiu')
            ->leftJoin('fiu.skills', 'skills')
            ->leftJoin('skills.skill', 'skill')
            ->leftJoin('skills.level', 'lvl')
            ->where('fiu.businessUnit = :buId')
            ->setParameter('buId', $bu->getId(), Types::INTEGER)
            ->orderBy('fiu.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Returns a map of skillId → highest acquired Level for the given user.
     *
     * @return array<int, Level|null>
     */
    private function fetchAcquiredLevels(int $userId): array
    {
        /** @var SkillRelUser[] $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('sru', 'sk', 'lvl')
            ->from(SkillRelUser::class, 'sru')
            ->join('sru.skill', 'sk')
            ->leftJoin('sru.acquiredLevel', 'lvl')
            ->where('sru.user = :userId')
            ->setParameter('userId', $userId, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $map = [];
        foreach ($rows as $sru) {
            $skill = $sru->getSkill();
            if (null === $skill) {
                continue;
            }
            $skillId = (int) $skill->getId();
            $existing = $map[$skillId] ?? null;
            $current = $sru->getAcquiredLevel();
            if (null === $existing || (null !== $current && $current->getPosition() > $existing->getPosition())) {
                $map[$skillId] = $current;
            }
        }

        return $map;
    }

    /**
     * Returns a map of profileId → number of levels.
     *
     * @return array<int, int>
     */
    private function fetchProfileLevelCounts(): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('IDENTITY(lvl.profile) AS profileId, COUNT(lvl.id) AS cnt')
            ->from(Level::class, 'lvl')
            ->where('lvl.profile IS NOT NULL')
            ->groupBy('lvl.profile')
            ->getQuery()
            ->getArrayResult()
        ;

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['profileId']] = (int) $row['cnt'];
        }

        return $map;
    }

    /**
     * @param array<int, Level|null> $acquiredLevelBySkillId
     * @param array<int, int>        $profileLevelCounts
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildSkillGaps(
        FunctionInUnit $fiu,
        array $acquiredLevelBySkillId,
        array $profileLevelCounts,
    ): array {
        $gaps = [];

        foreach ($fiu->getSkills() as $fiuSkill) {
            /** @var FunctionInUnitSkill $fiuSkill */
            $skill = $fiuSkill->getSkill();
            $requiredLevel = $fiuSkill->getLevel();
            $skillId = (int) $skill->getId();

            $profileId = $skill->getLevelProfile()?->getId();
            $totalLevels = null !== $profileId ? ($profileLevelCounts[$profileId] ?? 1) : 1;

            $requiredPosition = null !== $requiredLevel ? $requiredLevel->getPosition() : null;
            $requiredPct = null !== $requiredPosition
                ? round(($requiredPosition + 1) / $totalLevels * 100, 1)
                : null;

            $acquiredLevel = $acquiredLevelBySkillId[$skillId] ?? null;
            $acquiredPosition = null !== $acquiredLevel ? $acquiredLevel->getPosition() : null;
            $acquiredPct = null !== $acquiredPosition
                ? round(($acquiredPosition + 1) / $totalLevels * 100, 1)
                : 0.0;

            $gaps[] = [
                'skillId' => $skillId,
                'skillTitle' => $skill->getTitle(),
                'requiredLevelTitle' => $requiredLevel?->getTitle(),
                'requiredLevelShortTitle' => $requiredLevel?->getShortTitle(),
                'requiredLevelPosition' => $requiredPosition,
                'requiredPercentage' => $requiredPct,
                'acquiredLevelTitle' => $acquiredLevel?->getTitle(),
                'acquiredLevelShortTitle' => $acquiredLevel?->getShortTitle(),
                'acquiredLevelPosition' => $acquiredPosition,
                'acquiredPercentage' => $acquiredPct,
                'totalLevels' => $totalLevels,
                'isMet' => null !== $requiredPosition && null !== $acquiredPosition
                    && $acquiredPosition >= $requiredPosition,
            ];
        }

        return $gaps;
    }
}
