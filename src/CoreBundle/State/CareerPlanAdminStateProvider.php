<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CareerPlanGroup;
use Chamilo\CoreBundle\Entity\FunctionInUnit;
use Chamilo\CoreBundle\Entity\FunctionInUnitSkill;
use Chamilo\CoreBundle\Entity\Level;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProviderInterface<CareerPlanGroup>
 */
final class CareerPlanAdminStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * @return CareerPlanGroup[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var FunctionInUnit[] $functions */
        $functions = $this->em->createQueryBuilder()
            ->select('fiu', 'bu', 'pf', 'buParent', 'skills', 'skill', 'lvl')
            ->from(FunctionInUnit::class, 'fiu')
            ->join('fiu.businessUnit', 'bu')
            ->join('fiu.professionalFunction', 'pf')
            ->leftJoin('bu.parent', 'buParent')
            ->leftJoin('fiu.skills', 'skills')
            ->leftJoin('skills.skill', 'skill')
            ->leftJoin('skills.level', 'lvl')
            ->orderBy('buParent.title', 'ASC')
            ->addOrderBy('bu.title', 'ASC')
            ->addOrderBy('fiu.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $profileLevelCounts = $this->fetchProfileLevelCounts();

        /** @var array<int, CareerPlanGroup> $grouped */
        $grouped = [];

        foreach ($functions as $fiu) {
            $bu = $fiu->getBusinessUnit();
            $buId = (int) $bu->getId();

            if (!isset($grouped[$buId])) {
                $group = new CareerPlanGroup();
                $group->businessUnitId = $buId;
                $group->businessUnitTitle = $bu->getTitle();
                $group->parentTitle = $bu->getParent()?->getTitle();
                $grouped[$buId] = $group;
            }

            $grouped[$buId]->functions[] = $this->serializeFunction($fiu, $profileLevelCounts);
        }

        return array_values($grouped);
    }

    /**
     * @param array<int, int> $profileLevelCounts
     *
     * @return array<string, mixed>
     */
    private function serializeFunction(FunctionInUnit $fiu, array $profileLevelCounts): array
    {
        $skills = [];
        foreach ($fiu->getSkills() as $fiuSkill) {
            /** @var FunctionInUnitSkill $fiuSkill */
            $skill = $fiuSkill->getSkill();
            $level = $fiuSkill->getLevel();

            $profileId = $skill->getLevelProfile()?->getId();
            $totalLevels = null !== $profileId ? ($profileLevelCounts[$profileId] ?? 1) : 1;
            $requiredPosition = null !== $level ? $level->getPosition() : null;
            $requiredPct = null !== $requiredPosition
                ? round(($requiredPosition + 1) / $totalLevels * 100, 1)
                : null;

            $skills[] = [
                'skillId' => $skill->getId(),
                'skillTitle' => $skill->getTitle(),
                'requiredLevelTitle' => $level?->getTitle(),
                'requiredLevelShortTitle' => $level?->getShortTitle(),
                'requiredLevelPosition' => $requiredPosition,
                'requiredPercentage' => $requiredPct,
                'totalLevels' => $totalLevels,
            ];
        }

        return [
            'id' => $fiu->getId(),
            'title' => $fiu->getTitle(),
            'professionalFunctionTitle' => $fiu->getProfessionalFunction()->getTitle(),
            'description' => $fiu->getDescription(),
            'skills' => $skills,
        ];
    }

    /**
     * Returns a map of profileId → number of levels in the profile.
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
}
