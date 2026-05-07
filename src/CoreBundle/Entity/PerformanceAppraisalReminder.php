<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

// Chamilo HR extension

use Chamilo\CoreBundle\Repository\PerformanceAppraisalReminderRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'performance_appraisal_reminder')]
#[ORM\Entity(repositoryClass: PerformanceAppraisalReminderRepository::class)]
class PerformanceAppraisalReminder
{
    /**
     * Offset in days from scheduledAt (negative = before, positive = after).
     */
    public const OFFSETS = [-14, -7, -3, -1, 0, 1, 3, 7, 14];

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PerformanceAppraisal::class)]
    #[ORM\JoinColumn(name: 'appraisal_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected PerformanceAppraisal $appraisal;

    #[ORM\Column(name: 'scheduled_at', type: 'datetime')]
    protected DateTime $scheduledAt;

    #[ORM\Column(name: 'offset_days', type: 'integer')]
    protected int $offsetDays;

    /**
     * True when this is the +14d "still incomplete" report to HR admins.
     */
    #[ORM\Column(name: 'is_report', type: 'boolean', options: ['default' => false])]
    protected bool $isReport = false;

    #[ORM\Column(name: 'sent', type: 'boolean', options: ['default' => false])]
    protected bool $sent = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppraisal(): PerformanceAppraisal
    {
        return $this->appraisal;
    }

    public function setAppraisal(PerformanceAppraisal $appraisal): self
    {
        $this->appraisal = $appraisal;

        return $this;
    }

    public function getScheduledAt(): DateTime
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(DateTime $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getOffsetDays(): int
    {
        return $this->offsetDays;
    }

    public function setOffsetDays(int $offsetDays): self
    {
        $this->offsetDays = $offsetDays;

        return $this;
    }

    public function isReport(): bool
    {
        return $this->isReport;
    }

    public function setIsReport(bool $isReport): self
    {
        $this->isReport = $isReport;

        return $this;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function setSent(bool $sent): self
    {
        $this->sent = $sent;

        return $this;
    }
}
