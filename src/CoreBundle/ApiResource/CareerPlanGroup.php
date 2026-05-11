<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

// Chamilo HR extension

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Chamilo\CoreBundle\State\CareerPlanAdminStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Represents one business unit row in the admin Career Plan overview.
 * Each instance groups all FunctionInUnit for a given BusinessUnit with
 * their required skills and skill-level percentages.
 */
#[ApiResource(
    shortName: 'CareerPlanGroup',
    operations: [
        new GetCollection(
            uriTemplate: '/career_plan/overview',
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')",
            paginationEnabled: false,
            normalizationContext: ['groups' => ['career_plan_group:read']],
            provider: CareerPlanAdminStateProvider::class,
        ),
    ],
)]
final class CareerPlanGroup
{
    #[ApiProperty(identifier: true)]
    #[Groups(['career_plan_group:read'])]
    public int $businessUnitId;

    #[Groups(['career_plan_group:read'])]
    public string $businessUnitTitle;

    #[Groups(['career_plan_group:read'])]
    public ?string $parentTitle = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['career_plan_group:read'])]
    public array $functions = [];
}
