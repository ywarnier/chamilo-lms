<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

// Chamilo HR extension

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\State\MyCareerPlanStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Returns the career plan for the currently authenticated user:
 * their current position(s) and the list of career targets (sibling and
 * parent functions in the org tree) with per-skill gap data.
 */
#[ApiResource(
    shortName: 'MyCareerPlan',
    operations: [
        new Get(
            uriTemplate: '/my_career_plan',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            normalizationContext: ['groups' => ['my_career_plan:read']],
            provider: MyCareerPlanStateProvider::class,
        ),
    ],
)]
final class MyCareerPlan
{
    #[ApiProperty(identifier: true, readable: false, writable: false)]
    public function getId(): string
    {
        return 'my-career-plan';
    }

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['my_career_plan:read'])]
    public array $currentPositions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['my_career_plan:read'])]
    public array $targets = [];
}
