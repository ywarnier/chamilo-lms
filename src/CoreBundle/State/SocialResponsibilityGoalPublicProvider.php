<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\SocialResponsibilityGoal;
use Chamilo\CoreBundle\Repository\SocialResponsibilityGoalRepository;

/**
 * Public read-side provider for SDG goals.
 *
 * Returns all published goals for the requested language (default 'en_US'),
 * falling back to the base locale (e.g. 'en' for 'en_US') when no exact match
 * is published. The `language` query parameter is parsed from API Platform's
 * standard `$context['filters']` array.
 *
 * @implements ProviderInterface<SocialResponsibilityGoal>
 */
final class SocialResponsibilityGoalPublicProvider implements ProviderInterface
{
    public function __construct(
        private readonly SocialResponsibilityGoalRepository $repository,
    ) {}

    /**
     * @return SocialResponsibilityGoal[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $rawLanguage = $context['filters']['language'] ?? 'en_US';
        $language = str_replace('-', '_', (string) $rawLanguage);

        $goals = $this->repository->findPublished($language);

        if ([] === $goals) {
            $base = (string) preg_replace('/_.*$/', '', $language);

            if ($base !== $language) {
                $goals = $this->repository->findPublished($base);
            }
        }

        return $goals;
    }
}
