<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Hr;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\FunctionInUnit;
use Chamilo\CoreBundle\Entity\JobOfferApplication;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_HR")'))]
class HrJobOfferDataController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route('/hr/function-required-skills/{fiuId}', name: 'hr_function_required_skills', methods: ['GET'], requirements: ['fiuId' => '\d+'])]
    public function functionRequiredSkills(int $fiuId): JsonResponse
    {
        $fiu = $this->em->getRepository(FunctionInUnit::class)->find($fiuId);
        if (null === $fiu) {
            return $this->json([]);
        }

        $data = [];
        foreach ($fiu->getSkills() as $skill) {
            $data[] = [
                'skillId' => $skill->getSkill()->getId(),
                'skillTitle' => $skill->getSkillTitle(),
                'levelTitle' => $skill->getLevelTitle(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/hr/user-skills/{userId}', name: 'hr_user_skills', methods: ['GET'], requirements: ['userId' => '\d+'])]
    public function userSkills(int $userId): JsonResponse
    {
        $user = $this->em->getRepository(User::class)->find($userId);
        if (null === $user) {
            return $this->json([]);
        }

        $skillRelUsers = $this->em->createQueryBuilder()
            ->select('sru')
            ->from(SkillRelUser::class, 'sru')
            ->join('sru.skill', 's')
            ->where('sru.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;

        $data = array_map(static fn (SkillRelUser $sru) => [
            'skillId' => $sru->getSkill()?->getId(),
            'skillTitle' => $sru->getSkill()?->getTitle(),
            'levelTitle' => $sru->getAcquiredLevel()?->getTitle(),
        ], $skillRelUsers);

        return $this->json($data);
    }

    #[Route('/hr/job-offer-application/{id}/file-info/{type}', name: 'hr_job_offer_application_file_info', methods: ['GET'], requirements: ['id' => '\d+', 'type' => 'cv|motivation'])]
    public function applicationFileInfo(int $id, string $type): JsonResponse
    {
        $application = $this->em->getRepository(JobOfferApplication::class)->find($id);
        if (null === $application) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $file = 'cv' === $type ? $application->getCvFile() : $application->getMotivationLetterFile();

        if (null === $file) {
            return $this->json(['url' => null, 'title' => null]);
        }

        return $this->json([
            'title' => $file->getTitle(),
            'url' => $this->buildDownloadUrl($file),
        ]);
    }

    private function buildDownloadUrl(PersonalFile $file): ?string
    {
        if (!$file->hasResourceNode()) {
            return null;
        }

        $resourceNode = $file->getResourceNode();
        $params = [
            'id' => $resourceNode->getUuid(),
            'tool' => $resourceNode->getResourceType()->getTool()->getTitle(),
            'type' => $resourceNode->getResourceType()->getTitle(),
        ];

        return $this->urlGenerator->generate(
            'chamilo_core_resource_download',
            $params,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
