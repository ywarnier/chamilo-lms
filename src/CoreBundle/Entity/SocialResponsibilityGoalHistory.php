<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

// Chamilo HR extension

use Chamilo\CoreBundle\Repository\SocialResponsibilityGoalHistoryRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'social_responsibility_goal_history')]
#[ORM\Entity(repositoryClass: SocialResponsibilityGoalHistoryRepository::class)]
class SocialResponsibilityGoalHistory
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SocialResponsibilityGoal::class)]
    #[ORM\JoinColumn(name: 'goal_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected SocialResponsibilityGoal $goal;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'enforcement', type: 'text', nullable: true)]
    protected ?string $enforcement = null;

    #[ORM\Column(name: 'is_published', type: 'boolean')]
    protected bool $isPublished = false;

    #[ORM\Column(name: 'changed_at', type: 'datetime')]
    protected DateTimeInterface $changedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'changed_by', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $changedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGoal(): SocialResponsibilityGoal
    {
        return $this->goal;
    }

    public function setGoal(SocialResponsibilityGoal $goal): self
    {
        $this->goal = $goal;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getEnforcement(): ?string
    {
        return $this->enforcement;
    }

    public function setEnforcement(?string $enforcement): self
    {
        $this->enforcement = $enforcement;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getChangedAt(): DateTimeInterface
    {
        return $this->changedAt;
    }

    public function setChangedAt(DateTimeInterface $changedAt): self
    {
        $this->changedAt = $changedAt;

        return $this;
    }

    public function getChangedBy(): ?User
    {
        return $this->changedBy;
    }

    public function setChangedBy(?User $changedBy): self
    {
        $this->changedBy = $changedBy;

        return $this;
    }
}
