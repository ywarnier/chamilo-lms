<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

// Chamilo HR extension

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Repository\PerformanceAppraisalRepository;
use Chamilo\CoreBundle\State\MyEvaluationsStateProvider;
use Chamilo\CoreBundle\State\PerformanceAppraisalStateProcessor;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new GetCollection(
            uriTemplate: '/me/performance_appraisals.{_format}',
            paginationEnabled: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            provider: MyEvaluationsStateProvider::class,
        ),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR') or object.getEvaluatorUser() == user or object.getEvaluatedUser() == user"),
        new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')", processor: PerformanceAppraisalStateProcessor::class),
        new Put(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
    ],
    normalizationContext: ['groups' => ['performance_appraisal:read']],
    denormalizationContext: ['groups' => ['performance_appraisal:write']],
)]
#[ORM\Table(name: 'performance_appraisal')]
#[ORM\Entity(repositoryClass: PerformanceAppraisalRepository::class)]
class PerformanceAppraisal
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_DONE = 'done';
    public const STATUS_FEEDBACKED = 'feedbacked';
    public const STATUS_CLOSED = 'closed';

    #[Groups(['performance_appraisal:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotNull]
    #[Groups(['performance_appraisal:read', 'performance_appraisal:write'])]
    #[ORM\ManyToOne(targetEntity: PerformanceAppraisalTemplate::class)]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    protected PerformanceAppraisalTemplate $template;

    #[Assert\NotNull]
    #[Groups(['performance_appraisal:read', 'performance_appraisal:write'])]
    #[ORM\ManyToOne(targetEntity: RecruitmentStage::class)]
    #[ORM\JoinColumn(name: 'stage_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    protected RecruitmentStage $stage;

    #[Assert\NotNull]
    #[Groups(['performance_appraisal:read', 'performance_appraisal:write'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'evaluator_user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $evaluatorUser;

    #[Assert\NotNull]
    #[Groups(['performance_appraisal:read', 'performance_appraisal:write'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'evaluated_user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $evaluatedUser;

    #[Assert\NotNull]
    #[Groups(['performance_appraisal:read', 'performance_appraisal:write'])]
    #[ORM\Column(name: 'scheduled_at', type: 'datetime')]
    protected DateTime $scheduledAt;

    #[Groups(['performance_appraisal:read'])]
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected DateTime $createdAt;

    #[Groups(['performance_appraisal:read'])]
    #[ORM\Column(name: 'status', type: 'string', length: 20, options: ['default' => self::STATUS_SCHEDULED])]
    protected string $status = self::STATUS_SCHEDULED;

    #[Groups(['performance_appraisal:read'])]
    #[ORM\Column(name: 'total_score', type: 'float', nullable: true)]
    protected ?float $totalScore = null;

    #[Groups(['performance_appraisal:read'])]
    #[ORM\OneToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'action_plan_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?self $actionPlan = null;

    /**
     * @var Collection<int, PerformanceAppraisalItem>
     */
    #[Groups(['performance_appraisal:read'])]
    #[ORM\OneToMany(
        mappedBy: 'appraisal',
        targetEntity: PerformanceAppraisalItem::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    protected Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new DateTime();
    }

    public static function getStatusName(string $status): string
    {
        return match ($status) {
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_DONE => 'Done',
            self::STATUS_FEEDBACKED => 'Feedbacked',
            self::STATUS_CLOSED => 'Closed',
            default => $status,
        };
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTemplate(): PerformanceAppraisalTemplate
    {
        return $this->template;
    }

    public function setTemplate(PerformanceAppraisalTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getStage(): RecruitmentStage
    {
        return $this->stage;
    }

    public function setStage(RecruitmentStage $stage): self
    {
        $this->stage = $stage;

        return $this;
    }

    public function getEvaluatorUser(): User
    {
        return $this->evaluatorUser;
    }

    public function setEvaluatorUser(User $evaluatorUser): self
    {
        $this->evaluatorUser = $evaluatorUser;

        return $this;
    }

    public function getEvaluatedUser(): User
    {
        return $this->evaluatedUser;
    }

    public function setEvaluatedUser(User $evaluatedUser): self
    {
        $this->evaluatedUser = $evaluatedUser;

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

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTotalScore(): ?float
    {
        return $this->totalScore;
    }

    public function setTotalScore(?float $totalScore): self
    {
        $this->totalScore = $totalScore;

        return $this;
    }

    public function getActionPlan(): ?self
    {
        return $this->actionPlan;
    }

    public function setActionPlan(?self $actionPlan): self
    {
        $this->actionPlan = $actionPlan;

        return $this;
    }

    /**
     * @return Collection<int, PerformanceAppraisalItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(PerformanceAppraisalItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setAppraisal($this);
        }

        return $this;
    }

    public function removeItem(PerformanceAppraisalItem $item): self
    {
        $this->items->removeElement($item);

        return $this;
    }

    public function removeAllItems(): self
    {
        $this->items->clear();

        return $this;
    }

    /**
     * @return Collection<int, PerformanceAppraisalItem>
     */
    public function getSkillItems(): Collection
    {
        return $this->items->filter(
            fn (PerformanceAppraisalItem $item) => PerformanceAppraisalItem::TYPE_SKILL === $item->getType()
        );
    }

    /**
     * @return Collection<int, PerformanceAppraisalItem>
     */
    public function getActivityItems(): Collection
    {
        return $this->items->filter(
            fn (PerformanceAppraisalItem $item) => PerformanceAppraisalItem::TYPE_ACTIVITY === $item->getType()
        );
    }

    /**
     * @return Collection<int, PerformanceAppraisalItem>
     */
    public function getObjectiveItems(): Collection
    {
        return $this->items->filter(
            fn (PerformanceAppraisalItem $item) => PerformanceAppraisalItem::TYPE_OBJECTIVE === $item->getType()
        );
    }

    public function calcTotalScore(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->calcScore();
        }

        return $total;
    }

    public function calcTotals(): void
    {
        $this->totalScore = $this->calcTotalScore();
    }
}
