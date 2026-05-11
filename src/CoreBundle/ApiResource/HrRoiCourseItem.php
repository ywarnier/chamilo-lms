<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

// Chamilo HR extension

use Symfony\Component\Serializer\Attribute\Groups;

final class HrRoiCourseItem
{
    #[Groups(['hr_roi_course:read'])]
    public int $sessionId;

    #[Groups(['hr_roi_course:read'])]
    public string $title;

    #[Groups(['hr_roi_course:read'])]
    public ?string $accessStartDate = null;

    #[Groups(['hr_roi_course:read'])]
    public ?string $accessEndDate = null;

    #[Groups(['hr_roi_course:read'])]
    public int $learnerCount = 0;

    #[Groups(['hr_roi_course:read'])]
    public float $cost = 0.0;

    #[Groups(['hr_roi_course:read'])]
    public ?float $costPerLearner = null;
}
