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
use Chamilo\CoreBundle\Repository\PerformanceAppraisalTemplateRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Put(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
    ],
    normalizationContext: ['groups' => ['performance_appraisal_template:read']],
    denormalizationContext: ['groups' => ['performance_appraisal_template:write']],
)]
#[ORM\Table(name: 'performance_appraisal_template')]
#[ORM\Entity(repositoryClass: PerformanceAppraisalTemplateRepository::class)]
class PerformanceAppraisalTemplate implements Stringable
{
    #[Groups(['performance_appraisal_template:read', 'performance_appraisal:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['performance_appraisal_template:read', 'performance_appraisal_template:write', 'performance_appraisal:read'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    protected string $title;

    #[Groups(['performance_appraisal_template:read', 'performance_appraisal_template:write', 'performance_appraisal:read'])]
    #[ORM\ManyToOne(targetEntity: Periodicity::class)]
    #[ORM\JoinColumn(name: 'periodicity_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Periodicity $periodicity = null;

    #[Groups(['performance_appraisal_template:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $createdBy = null;

    #[Groups(['performance_appraisal_template:read'])]
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    protected ?DateTime $createdAt = null;

    /**
     * @var Collection<int, PerformanceAppraisalTemplateItem>
     */
    #[Groups(['performance_appraisal_template:read', 'performance_appraisal_template:write'])]
    #[ORM\OneToMany(
        mappedBy: 'template',
        targetEntity: PerformanceAppraisalTemplateItem::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    protected Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPeriodicity(): ?Periodicity
    {
        return $this->periodicity;
    }

    public function setPeriodicity(?Periodicity $periodicity): self
    {
        $this->periodicity = $periodicity;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, PerformanceAppraisalTemplateItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(PerformanceAppraisalTemplateItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setTemplate($this);
        }

        return $this;
    }

    public function removeItem(PerformanceAppraisalTemplateItem $item): self
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
     * @return Collection<int, PerformanceAppraisalTemplateItem>
     */
    public function getSkills(): Collection
    {
        return $this->items->filter(
            fn (PerformanceAppraisalTemplateItem $item) => PerformanceAppraisalTemplateItem::TYPE_SKILL === $item->getType()
        );
    }

    /**
     * @return Collection<int, PerformanceAppraisalTemplateItem>
     */
    public function getActivities(): Collection
    {
        return $this->items->filter(
            fn (PerformanceAppraisalTemplateItem $item) => PerformanceAppraisalTemplateItem::TYPE_ACTIVITY === $item->getType()
        );
    }

    /**
     * @return Collection<int, PerformanceAppraisalTemplateItem>
     */
    public function getObjectives(): Collection
    {
        return $this->items->filter(
            fn (PerformanceAppraisalTemplateItem $item) => PerformanceAppraisalTemplateItem::TYPE_OBJECTIVE === $item->getType()
        );
    }
}
