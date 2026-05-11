<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Hr;

// Chamilo HR extension

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\BranchSync;
use Chamilo\CoreBundle\Entity\GeographicZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_HR")'))]
class HrBranchController extends BaseController
{
    private const BRANCH_TYPE = 'hr';

    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('/hr/branches-data', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $branches = $this->em->createQueryBuilder()
            ->select('b', 'gz')
            ->from(BranchSync::class, 'b')
            ->leftJoin('b.geographicZone', 'gz')
            ->where('b.branchType = :type')
            ->setParameter('type', self::BRANCH_TYPE)
            ->orderBy('b.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $members = array_map(fn (BranchSync $b) => $this->serializeBranch($b), $branches);

        return $this->json([
            '@context' => '/api/contexts/BranchSync',
            '@id' => '/hr/branches-data',
            '@type' => 'hydra:Collection',
            'hydra:member' => $members,
            'hydra:totalItems' => \count($members),
        ]);
    }

    #[Route('/hr/branches-data', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $branch = new BranchSync();
        $branch->setBranchType(self::BRANCH_TYPE);

        $accessUrl = $this->em->getRepository(AccessUrl::class)->findOneBy([], ['id' => 'ASC']);
        if (null !== $accessUrl) {
            $branch->setUrl($accessUrl);
        }

        $this->applyPayload($branch, $data);

        $this->em->persist($branch);
        $this->em->flush();

        return $this->json($this->serializeBranch($branch), Response::HTTP_CREATED);
    }

    #[Route('/hr/branches-data/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $branch = $this->findHrBranch($id);
        $data = json_decode($request->getContent(), true) ?? [];

        $this->applyPayload($branch, $data);
        $this->em->flush();

        return $this->json($this->serializeBranch($branch));
    }

    #[Route('/hr/branches-data/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $branch = $this->findHrBranch($id);
        $this->em->remove($branch);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function findHrBranch(int $id): BranchSync
    {
        $branch = $this->em->getRepository(BranchSync::class)->find($id);

        if (null === $branch || self::BRANCH_TYPE !== $branch->getBranchType()) {
            throw $this->createNotFoundException();
        }

        return $branch;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyPayload(BranchSync $branch, array $data): void
    {
        if (isset($data['title'])) {
            $branch->setTitle((string) $data['title']);
        }

        $branch->setDescription((string) ($data['address'] ?? ''));
        $branch->setLatitude(isset($data['latitude']) && '' !== $data['latitude'] ? (string) $data['latitude'] : null);
        $branch->setLongitude(isset($data['longitude']) && '' !== $data['longitude'] ? (string) $data['longitude'] : null);

        $geoZoneIri = $data['geographicZone'] ?? null;
        if (null !== $geoZoneIri && '' !== $geoZoneIri) {
            $geoId = (int) preg_replace('/\D/', '', (string) $geoZoneIri);
            $zone = $this->em->getRepository(GeographicZone::class)->find($geoId);
            $branch->setGeographicZone($zone);
        } else {
            $branch->setGeographicZone(null);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeBranch(BranchSync $b): array
    {
        $zone = $b->getGeographicZone();

        return [
            '@id' => '/api/branches/'.$b->getId(),
            'id' => $b->getId(),
            'title' => $b->getTitle(),
            'address' => $b->getDescription(),
            'latitude' => $b->getLatitude(),
            'longitude' => $b->getLongitude(),
            'geographicZone' => null !== $zone ? [
                '@id' => '/api/geographic_zones/'.$zone->getId(),
                'id' => $zone->getId(),
                'title' => $zone->getTitle(),
            ] : null,
        ];
    }
}
