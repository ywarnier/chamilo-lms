<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Hr;

// Chamilo HR extension

use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\SocialResponsibilityGoal;
use Chamilo\CoreBundle\Entity\SocialResponsibilityGoalHistory;
use Chamilo\CoreBundle\Repository\SocialResponsibilityGoalHistoryRepository;
use Chamilo\CoreBundle\Repository\SocialResponsibilityGoalRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

class SocialResponsibilityController extends BaseController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SocialResponsibilityGoalRepository $goalRepository,
        private readonly SocialResponsibilityGoalHistoryRepository $historyRepository,
        private readonly AiProviderFactory $aiProviderFactory,
    ) {}

    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_HR")'))]
    #[Route('/hr/social-responsibility/languages', name: 'hr_sdg_languages', methods: ['GET'])]
    public function languages(): JsonResponse
    {
        $rows = $this->em->createQueryBuilder()
            ->select('l.isocode, l.englishName')
            ->from(Language::class, 'l')
            ->where('l.available = true')
            ->orderBy('l.englishName', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return $this->json(array_map(
            fn (array $r) => ['isocode' => $r['isocode'], 'englishName' => ucfirst($r['englishName'])],
            $rows
        ));
    }

    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_HR")'))]
    #[Route('/hr/social-responsibility/{id}/history', name: 'hr_sdg_history', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function history(int $id): JsonResponse
    {
        $goal = $this->em->find(SocialResponsibilityGoal::class, $id);

        if (null === $goal) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $entries = $this->historyRepository->findByGoal($goal);

        $rows = array_map(fn ($h) => [
            'id' => $h->getId(),
            'description' => $h->getDescription(),
            'enforcement' => $h->getEnforcement(),
            'isPublished' => $h->isPublished(),
            'changedAt' => $h->getChangedAt()->format(DateTimeInterface::ATOM),
            'changedBy' => $h->getChangedBy()?->getFullName(),
        ], $entries);

        return $this->json($rows);
    }

    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_HR")'))]
    #[Route('/hr/social-responsibility/translate', name: 'hr_sdg_translate', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        if (!$this->aiProviderFactory->hasProvidersForType('text')) {
            return $this->json(
                ['error' => 'No AI text provider is configured. Please configure one in the AI settings.'],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $goalId = isset($data['goalId']) ? (int) $data['goalId'] : 0;
        $targetLanguage = isset($data['targetLanguage']) ? (string) $data['targetLanguage'] : '';

        if (0 === $goalId || '' === $targetLanguage) {
            return $this->json(['error' => 'goalId and targetLanguage are required.'], Response::HTTP_BAD_REQUEST);
        }

        $goal = $this->em->find(SocialResponsibilityGoal::class, $goalId);

        if (null === $goal) {
            return $this->json(['error' => 'Goal not found.'], Response::HTTP_NOT_FOUND);
        }

        if ('' === (string) $goal->getDescription() && '' === (string) $goal->getEnforcement()) {
            return $this->json(['error' => 'The source goal has no content to translate.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $provider = $this->aiProviderFactory->getProvider(null, 'text');
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $sourceLang = $goal->getLanguage();
        $prompt = 'You are a professional translator. Translate the following SDG goal content from "'
            .$sourceLang.'" to "'.$targetLanguage.'". '
            .'Return ONLY a valid JSON object with exactly three keys: "title", "description" and "enforcement". '
            .'Do not add any explanation or markdown outside the JSON.'
            ."\n\nTitle: ".$goal->getTitle()
            ."\n\nDescription:\n".(string) $goal->getDescription()
            ."\n\nEnforcement:\n".(string) $goal->getEnforcement();

        try {
            if (method_exists($provider, 'generateText')) {
                $raw = (string) $provider->generateText($prompt);
            } elseif (method_exists($provider, 'chat')) {
                $raw = (string) $provider->chat([['role' => 'user', 'content' => $prompt]]);
            } else {
                return $this->json(['error' => 'Configured AI provider does not support text generation.'], Response::HTTP_SERVICE_UNAVAILABLE);
            }
        } catch (Throwable $e) {
            return $this->json(['error' => 'AI provider error: '.$e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        // Strip possible markdown code fences
        $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $raw = preg_replace('/\s*```$/', '', $raw ?? '');

        $decoded = json_decode($raw ?? '', true);

        if (!\is_array($decoded) || !\array_key_exists('title', $decoded)) {
            return $this->json(['error' => 'AI returned an unexpected response format.', 'raw' => $raw], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json([
            'title' => $decoded['title'] ?? '',
            'description' => $decoded['description'] ?? '',
            'enforcement' => $decoded['enforcement'] ?? '',
        ]);
    }

    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_HR")'))]
    #[Route('/hr/social-responsibility/save-translation', name: 'hr_sdg_save_translation', methods: ['POST'])]
    public function saveTranslation(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $sdgNumber = isset($data['sdgNumber']) ? (int) $data['sdgNumber'] : 0;
        $language = isset($data['language']) ? trim((string) $data['language']) : '';
        $title = isset($data['title']) ? trim((string) $data['title']) : '';
        $description = isset($data['description']) ? (string) $data['description'] : null;
        $enforcement = isset($data['enforcement']) ? (string) $data['enforcement'] : null;

        if ($sdgNumber < 1 || $sdgNumber > 17 || '' === $language || '' === $title) {
            return $this->json(['error' => 'sdgNumber (1–17), language and title are required.'], Response::HTTP_BAD_REQUEST);
        }

        $goal = $this->goalRepository->findOneBy(['sdgNumber' => $sdgNumber, 'language' => $language]);

        if (null === $goal) {
            $goal = new SocialResponsibilityGoal();
            $goal->setSdgNumber($sdgNumber);
            $goal->setLanguage($language);
            $goal->setTitle($title);
        }

        $now = new DateTimeImmutable();
        $user = $this->getUser();

        if (null !== $goal->getId()) {
            $history = new SocialResponsibilityGoalHistory();
            $history
                ->setGoal($goal)
                ->setDescription($goal->getDescription())
                ->setEnforcement($goal->getEnforcement())
                ->setIsPublished($goal->getIsPublished())
                ->setChangedAt($now)
                ->setChangedBy($user)
            ;
            $this->em->persist($history);
        }

        $goal
            ->setDescription('' !== (string) $description ? $description : null)
            ->setEnforcement('' !== (string) $enforcement ? $enforcement : null)
            ->setUpdatedAt($now)
            ->setUpdatedBy($user)
        ;

        $this->em->persist($goal);
        $this->em->flush();

        return $this->json([
            'id' => $goal->getId(),
            'sdgNumber' => $goal->getSdgNumber(),
            'language' => $goal->getLanguage(),
            'title' => $goal->getTitle(),
        ], Response::HTTP_CREATED);
    }
}
