<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Admin;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\BranchSync;
use Chamilo\CoreBundle\Entity\BusinessUnit;
use Chamilo\CoreBundle\Entity\FunctionInUnit;
use Chamilo\CoreBundle\Entity\Level;
use Chamilo\CoreBundle\Entity\ProfessionalFunction;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserToFunctionInUnit;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_HR")'))]
#[Route('/hr')]
class HrOrganizationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('/hr-branches-data', name: 'hr_branches_data', methods: ['GET'])]
    public function hrBranchesData(): JsonResponse
    {
        $branches = $this->em->getRepository(BranchSync::class)
            ->createQueryBuilder('b')
            ->where('b.branchType = :type')
            ->setParameter('type', 'hr')
            ->orderBy('b.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $data = array_map(static fn (BranchSync $b) => [
            '@id' => '/api/branches/'.$b->getId(),
            'id' => $b->getId(),
            'title' => $b->getTitle(),
        ], $branches);

        return $this->json(['hydra:member' => $data]);
    }

    #[Route('/function-skills-data/{id}', name: 'hr_function_skills_data', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function functionSkillsData(int $id): JsonResponse
    {
        $fiu = $this->em->getRepository(FunctionInUnit::class)->find($id);
        if (null === $fiu) {
            return $this->json([]);
        }

        $data = [];
        foreach ($fiu->getSkills() as $skill) {
            $data[] = [
                'id' => $skill->getId(),
                'skillTitle' => $skill->getSkillTitle(),
                'levelTitle' => $skill->getLevelTitle(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/skills-data', name: 'hr_skills_data', methods: ['GET'])]
    public function skillsData(): JsonResponse
    {
        $rows = $this->em->createQueryBuilder()
            ->select('s.id, s.title')
            ->from(Skill::class, 's')
            ->orderBy('s.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $data = array_map(static fn (array $row) => [
            'id' => $row['id'],
            '@id' => '/api/skills/'.$row['id'],
            'title' => $row['title'],
        ], $rows);

        return $this->json(['hydra:member' => $data]);
    }

    #[Route('/professional-functions-data', name: 'hr_professional_functions_data', methods: ['GET'])]
    public function professionalFunctionsData(): JsonResponse
    {
        $rows = $this->em->createQueryBuilder()
            ->select('f.id, f.title, f.startDate, f.endDate, p.title AS parentTitle')
            ->from(ProfessionalFunction::class, 'f')
            ->leftJoin('f.parent', 'p')
            ->orderBy('f.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $data = array_map(static fn (array $row) => [
            'id' => $row['id'],
            '@id' => '/api/professional_functions/'.$row['id'],
            'title' => $row['title'],
            'parentTitle' => $row['parentTitle'],
            'startDate' => $row['startDate'] instanceof DateTimeInterface ? $row['startDate']->format('Y-m-d') : null,
            'endDate' => $row['endDate'] instanceof DateTimeInterface ? $row['endDate']->format('Y-m-d') : null,
        ], $rows);

        return $this->json($data);
    }

    #[Route('/levels-data', name: 'hr_levels_data', methods: ['GET'])]
    public function levelsData(): JsonResponse
    {
        $levels = $this->em->getRepository(Level::class)
            ->createQueryBuilder('l')
            ->orderBy('l.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $data = array_map(static fn (Level $l) => [
            'id' => $l->getId(),
            '@id' => '/api/levels/'.$l->getId(),
            'title' => $l->getTitle(),
            'position' => $l->getPosition(),
        ], $levels);

        return $this->json(['hydra:member' => $data]);
    }

    #[Route('/unit-function-list-data', name: 'hr_unit_function_list_data', methods: ['GET'])]
    public function unitFunctionListData(): JsonResponse
    {
        $units = $this->em->getRepository(BusinessUnit::class)->findAll();

        $unitMap = [];
        foreach ($units as $unit) {
            $unitMap[$unit->getId()] = [
                'id' => $unit->getId(),
                'title' => $unit->getTitle(),
                'parentId' => $unit->getParent()?->getId(),
                'headcount' => 0,
                'functions' => [],
            ];
        }

        $functionInUnits = $this->em->getRepository(FunctionInUnit::class)->findAll();

        $unitPositionCounts = [];
        foreach ($functionInUnits as $fiu) {
            $unitId = $fiu->getBusinessUnit()->getId();
            if (!isset($unitMap[$unitId])) {
                continue;
            }

            $positions = $fiu->getPositions();
            $positionData = [];
            foreach ($positions as $position) {
                $positionData[] = [
                    'userId' => $position->getUser()->getId(),
                    'userFullName' => $position->getUser()->getFullName(),
                    'isBoss' => $position->isBoss(),
                    'contractEtp' => $position->getContractEtp(),
                ];
                $userId = $position->getUser()->getId();
                $unitPositionCounts[$userId] = ($unitPositionCounts[$userId] ?? 0) + 1;
            }

            $positionCount = \count($positionData);
            $unitMap[$unitId]['headcount'] += $positionCount;
            $unitMap[$unitId]['functions'][] = [
                'id' => $fiu->getId(),
                'title' => $fiu->getTitle(),
                'professionalFunctionTitle' => $fiu->getProfessionalFunctionTitle(),
                'positions' => $positionData,
                'headcount' => $positionCount,
            ];
        }

        // Build headcount totals per unit (including descendants)
        $tree = $this->buildTree(array_values($unitMap));

        return $this->json([
            'tree' => $tree,
            'totalHeadcount' => \count($unitPositionCounts),
        ]);
    }

    #[Route('/positions-data', name: 'hr_positions_data', methods: ['GET'])]
    public function positionsData(Request $request): JsonResponse
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p', 'u', 'fiu', 'pf', 'bu')
            ->from(UserToFunctionInUnit::class, 'p')
            ->join('p.user', 'u')
            ->join('p.functionInUnit', 'fiu')
            ->join('fiu.professionalFunction', 'pf')
            ->join('fiu.businessUnit', 'bu')
            ->orderBy('bu.title', 'ASC')
            ->addOrderBy('u.lastname', 'ASC')
        ;

        $functionId = $request->query->getInt('functionInUnit');
        if ($functionId > 0) {
            $qb->andWhere('fiu.id = :fiu')->setParameter('fiu', $functionId);
        }

        $userId = $request->query->getInt('user');
        if ($userId > 0) {
            $qb->andWhere('u.id = :user')->setParameter('user', $userId);
        }

        $positions = $qb->getQuery()->getResult();

        $data = array_map(static fn (UserToFunctionInUnit $p) => [
            'id' => $p->getId(),
            'userId' => $p->getUser()->getId(),
            'userIri' => '/api/users/'.$p->getUser()->getId(),
            'userFullName' => $p->getUser()->getFullName(),
            'userUsername' => $p->getUser()->getUsername(),
            'functionInUnitId' => $p->getFunctionInUnit()->getId(),
            'functionInUnitIri' => '/api/function_in_units/'.$p->getFunctionInUnit()->getId(),
            'functionInUnitTitle' => $p->getFunctionInUnit()->getTitle(),
            'professionalFunctionTitle' => $p->getFunctionInUnit()->getProfessionalFunctionTitle(),
            'businessUnitTitle' => $p->getFunctionInUnit()->getBusinessUnitTitle(),
            'startDate' => $p->getStartDate()->format('Y-m-d'),
            'endDate' => $p->getEndDate()?->format('Y-m-d'),
            'isBoss' => $p->isBoss(),
            'contractEtp' => $p->getContractEtp(),
            'branchTitle' => $p->getBranchTitle(),
            'geographicZoneTitle' => $p->getGeographicZoneTitle(),
        ], $positions);

        return $this->json($data);
    }

    #[Route('/competency-search-data', name: 'hr_competency_search_data', methods: ['GET'])]
    public function competencySearchData(Request $request): JsonResponse
    {
        $mode = $request->query->getString('mode', '');

        return match ($mode) {
            'by_skill' => $this->searchBySkill($request),
            'by_function' => $this->searchByFunction($request),
            'compare_users' => $this->compareUsers($request),
            'user_vs_function' => $this->userVsFunction($request),
            default => $this->json(['error' => 'Unknown mode'], 400),
        };
    }

    private function searchBySkill(Request $request): JsonResponse
    {
        $skillIds = array_map('intval', (array) $request->query->all('skill_ids'));
        $levelId = $request->query->getInt('level_id');

        if (empty($skillIds)) {
            return $this->json([]);
        }

        $qb = $this->em->createQueryBuilder()
            ->select('DISTINCT u.id, u.firstname, u.lastname, u.username')
            ->from(SkillRelUser::class, 'sru')
            ->join('sru.user', 'u')
            ->join('sru.skill', 's')
            ->where('s.id IN (:skillIds)')
            ->setParameter('skillIds', $skillIds)
            ->andWhere('u.status != -2')
        ;

        if ($levelId > 0) {
            $qb->join('sru.acquiredLevel', 'lv')
                ->andWhere('lv.id = :level')
                ->setParameter('level', $levelId)
            ;
        }

        $users = $qb->getQuery()->getArrayResult();

        return $this->json(array_values($users));
    }

    private function searchByFunction(Request $request): JsonResponse
    {
        $functionId = $request->query->getInt('function_in_unit_id');
        if ($functionId <= 0) {
            return $this->json([]);
        }

        $fiu = $this->em->getRepository(FunctionInUnit::class)->find($functionId);
        if (null === $fiu) {
            return $this->json([]);
        }

        $requiredSkills = [];
        foreach ($fiu->getSkills() as $fiuSkill) {
            $requiredSkills[$fiuSkill->getSkill()->getId()] = $fiuSkill->getLevel()?->getId();
        }

        if (empty($requiredSkills)) {
            return $this->json([]);
        }

        $skillIds = array_keys($requiredSkills);

        $rows = $this->em->createQueryBuilder()
            ->select('u.id, u.firstname, u.lastname, u.username, s.id AS skillId, lv.id AS levelId')
            ->from(SkillRelUser::class, 'sru')
            ->join('sru.user', 'u')
            ->join('sru.skill', 's')
            ->leftJoin('sru.acquiredLevel', 'lv')
            ->where('s.id IN (:skillIds)')
            ->setParameter('skillIds', $skillIds)
            ->andWhere('u.status != -2')
            ->getQuery()
            ->getArrayResult()
        ;

        $userSkills = [];
        foreach ($rows as $row) {
            $uid = $row['id'];
            $userSkills[$uid]['user'] = ['id' => $uid, 'firstname' => $row['firstname'], 'lastname' => $row['lastname'], 'username' => $row['username']];
            $userSkills[$uid]['skills'][$row['skillId']] = $row['levelId'];
        }

        $matches = [];
        foreach ($userSkills as $uid => $data) {
            $acquired = $data['skills'];
            $allMet = true;
            foreach ($requiredSkills as $sid => $reqLevelId) {
                if (!isset($acquired[$sid])) {
                    $allMet = false;

                    break;
                }
            }
            if ($allMet) {
                $matches[] = $data['user'];
            }
        }

        return $this->json(array_values($matches));
    }

    private function compareUsers(Request $request): JsonResponse
    {
        $userAId = $request->query->getInt('user_a');
        $userBId = $request->query->getInt('user_b');

        if ($userAId <= 0 || $userBId <= 0) {
            return $this->json([]);
        }

        $userA = $this->em->getRepository(User::class)->find($userAId);
        $userB = $this->em->getRepository(User::class)->find($userBId);

        if (null === $userA || null === $userB) {
            return $this->json([]);
        }

        $fetchUserSkills = function (User $u): array {
            $rows = $this->em->createQueryBuilder()
                ->select('s.id, s.title AS skillTitle, lv.id AS levelId, lv.title AS levelTitle')
                ->from(SkillRelUser::class, 'sru')
                ->join('sru.skill', 's')
                ->leftJoin('sru.acquiredLevel', 'lv')
                ->where('sru.user = :user')
                ->setParameter('user', $u->getId(), Types::INTEGER)
                ->getQuery()
                ->getArrayResult()
            ;
            $out = [];
            foreach ($rows as $row) {
                $out[$row['id']] = ['skillTitle' => $row['skillTitle'], 'levelId' => $row['levelId'], 'levelTitle' => $row['levelTitle']];
            }

            return $out;
        };

        $aSkills = $fetchUserSkills($userA);
        $bSkills = $fetchUserSkills($userB);

        $allSkillIds = array_unique(array_merge(array_keys($aSkills), array_keys($bSkills)));

        $rows = [];
        foreach ($allSkillIds as $sid) {
            $rows[] = [
                'skillId' => $sid,
                'skillTitle' => $aSkills[$sid]['skillTitle'] ?? $bSkills[$sid]['skillTitle'] ?? '',
                'userA' => $aSkills[$sid] ?? null,
                'userB' => $bSkills[$sid] ?? null,
            ];
        }

        return $this->json([
            'userA' => ['id' => $userA->getId(), 'fullName' => $userA->getFullName()],
            'userB' => ['id' => $userB->getId(), 'fullName' => $userB->getFullName()],
            'rows' => $rows,
        ]);
    }

    private function userVsFunction(Request $request): JsonResponse
    {
        $userId = $request->query->getInt('user_id');
        $functionId = $request->query->getInt('function_in_unit_id');

        if ($userId <= 0 || $functionId <= 0) {
            return $this->json([]);
        }

        $user = $this->em->getRepository(User::class)->find($userId);
        $fiu = $this->em->getRepository(FunctionInUnit::class)->find($functionId);

        if (null === $user || null === $fiu) {
            return $this->json([]);
        }

        $requiredSkills = [];
        foreach ($fiu->getSkills() as $fiuSkill) {
            $requiredSkills[$fiuSkill->getSkill()->getId()] = [
                'skillId' => $fiuSkill->getSkill()->getId(),
                'skillTitle' => $fiuSkill->getSkillTitle(),
                'requiredLevelId' => $fiuSkill->getLevel()?->getId(),
                'requiredLevelTitle' => $fiuSkill->getLevelTitle(),
            ];
        }

        $acquiredRows = $this->em->createQueryBuilder()
            ->select('s.id AS skillId, lv.id AS levelId, lv.position AS levelPosition')
            ->from(SkillRelUser::class, 'sru')
            ->join('sru.skill', 's')
            ->leftJoin('sru.acquiredLevel', 'lv')
            ->where('sru.user = :user')
            ->setParameter('user', $user->getId(), Types::INTEGER)
            ->getQuery()
            ->getArrayResult()
        ;

        $acquired = [];
        foreach ($acquiredRows as $row) {
            $acquired[$row['skillId']] = ['levelId' => $row['levelId'], 'levelPosition' => $row['levelPosition']];
        }

        $rows = [];
        foreach ($requiredSkills as $sid => $req) {
            $userHas = $acquired[$sid] ?? null;
            if (null === $userHas) {
                $status = 'missing';
            } elseif (null === $req['requiredLevelId']) {
                $status = 'ok';
            } else {
                $reqLevel = $this->em->getRepository(Level::class)->find($req['requiredLevelId']);
                $reqPos = $reqLevel?->getPosition() ?? 0;
                $status = ($userHas['levelPosition'] ?? 0) >= $reqPos ? 'ok' : 'below';
            }

            $rows[] = [
                'skillId' => $sid,
                'skillTitle' => $req['skillTitle'],
                'requiredLevelTitle' => $req['requiredLevelTitle'],
                'acquiredLevelId' => $userHas['levelId'] ?? null,
                'status' => $status,
            ];
        }

        return $this->json([
            'user' => ['id' => $user->getId(), 'fullName' => $user->getFullName()],
            'functionInUnit' => ['id' => $fiu->getId(), 'title' => $fiu->getTitle()],
            'rows' => $rows,
        ]);
    }

    /**
     * Build a nested tree from a flat list of units with parentId references.
     *
     * @param array<int, array<string, mixed>> $flat
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildTree(array $flat): array
    {
        $indexed = [];
        foreach ($flat as $node) {
            $indexed[$node['id']] = $node;
            $indexed[$node['id']]['children'] = [];
        }

        $roots = [];
        foreach ($indexed as $id => $node) {
            $parentId = $node['parentId'];
            if (null === $parentId || !isset($indexed[$parentId])) {
                $roots[] = &$indexed[$id];
            } else {
                $indexed[$parentId]['children'][] = &$indexed[$id];
            }
        }

        return $roots;
    }
}
