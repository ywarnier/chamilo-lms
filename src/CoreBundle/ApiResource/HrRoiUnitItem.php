<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

// Chamilo HR extension

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Chamilo\CoreBundle\State\HrRoiUnitStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'HrRoiUnit',
    operations: [
        new GetCollection(
            uriTemplate: '/hr_roi/unit',
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')",
            paginationEnabled: false,
            normalizationContext: ['groups' => ['hr_roi_unit:read']],
            provider: HrRoiUnitStateProvider::class,
            // Path B fallback: query aggregates per User across multi-hop relations
            // (User → UserToFunctionInUnit → FunctionInUnit → BusinessUnit) with no single
            // natural pivot entity. Filters are read manually from $context['filters'] in
            // the state provider; document them here so OpenAPI lists them.
            openapiContext: [
                'parameters' => [
                    [
                        'name' => 'unit',
                        'in' => 'query',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'BusinessUnit identifier (numeric ID).',
                    ],
                    [
                        'name' => 'accessStartDate[after]',
                        'in' => 'query',
                        'required' => false,
                        'schema' => ['type' => 'string', 'format' => 'date'],
                        'description' => 'Filter sessions whose accessStartDate is on or after this date (YYYY-MM-DD).',
                    ],
                    [
                        'name' => 'accessStartDate[before]',
                        'in' => 'query',
                        'required' => false,
                        'schema' => ['type' => 'string', 'format' => 'date'],
                        'description' => 'Filter sessions whose accessStartDate is on or before this date (YYYY-MM-DD).',
                    ],
                ],
            ],
        ),
    ],
)]
final class HrRoiUnitItem
{
    #[ApiProperty(identifier: true)]
    #[Groups(['hr_roi_unit:read'])]
    public int $userId;

    #[Groups(['hr_roi_unit:read'])]
    public string $userFullName;

    #[Groups(['hr_roi_unit:read'])]
    public int $sessionsCount = 0;

    #[Groups(['hr_roi_unit:read'])]
    public float $totalInvestment = 0.0;

    #[Groups(['hr_roi_unit:read'])]
    public float $averageCostPerSession = 0.0;
}
