<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\BusinessUnit;
use Chamilo\CoreBundle\Entity\FunctionInUnit;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class OrgChartController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SettingsManager $settingsManager,
    ) {}

    #[Route('/organizational-chart-data', name: 'org_chart_data', methods: ['GET'])]
    public function data(): JsonResponse
    {
        $unitPublic = 'true' === $this->settingsManager->getSetting('hr.skills_orga_unit_public');
        $peoplePublic = 'true' === $this->settingsManager->getSetting('hr.skills_orga_people_public');
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$unitPublic && !$peoplePublic && !$isAdmin) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $units = $this->em->getRepository(BusinessUnit::class)->findAll();
        $functionInUnits = $this->em->getRepository(FunctionInUnit::class)->findAll();

        // Index positions by function id
        $positionsByFunction = [];
        foreach ($functionInUnits as $fiu) {
            $fid = $fiu->getId();
            $positionsByFunction[$fid] = [];
            foreach ($fiu->getPositions() as $pos) {
                $positionsByFunction[$fid][] = $pos;
            }
        }

        // Build unit nodes
        $nodes = [];
        foreach ($units as $unit) {
            $parentId = $unit->getParent()?->getId();

            // Find boss for Tab A
            $bossName = null;
            $bossFunctionTitle = null;
            foreach ($functionInUnits as $fiu) {
                if ($fiu->getBusinessUnit()->getId() !== $unit->getId()) {
                    continue;
                }
                foreach ($fiu->getPositions() as $pos) {
                    if ($pos->isBoss()) {
                        $bossName = $pos->getUser()->getFullName();
                        $bossFunctionTitle = $fiu->getTitle();

                        break 2;
                    }
                }
            }

            // Collect all staff for Tab B
            $staff = [];
            foreach ($functionInUnits as $fiu) {
                if ($fiu->getBusinessUnit()->getId() !== $unit->getId()) {
                    continue;
                }
                foreach ($fiu->getPositions() as $pos) {
                    $staff[] = [
                        'fullName' => $pos->getUser()->getFullName(),
                        'functionTitle' => $fiu->getTitle(),
                        'isBoss' => $pos->isBoss(),
                    ];
                }
            }

            $nodes[] = [
                'id' => $unit->getId(),
                'parentId' => $parentId,
                'title' => $unit->getTitle(),
                'bossName' => $bossName,
                'bossFunctionTitle' => $bossFunctionTitle,
                'staff' => $staff,
                'headcount' => \count($staff),
            ];
        }

        return $this->json([
            'nodes' => $nodes,
            'settings' => [
                'unitPublic' => $unitPublic || $isAdmin,
                'peoplePublic' => $peoplePublic || $isAdmin,
            ],
        ]);
    }
}
