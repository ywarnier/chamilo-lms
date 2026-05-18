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
use Chamilo\CoreBundle\Repository\ProfessionalFunctionRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(paginationClientEnabled: true, security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Put(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
    ],
    normalizationContext: ['groups' => ['professional_function:read']],
    denormalizationContext: ['groups' => ['professional_function:write']],
)]
#[ORM\Table(name: 'professional_function')]
#[ORM\Entity(repositoryClass: ProfessionalFunctionRepository::class)]
class ProfessionalFunction
{
    #[Groups(['professional_function:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Groups(['professional_function:read', 'professional_function:write'])]
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    #[Assert\NotBlank]
    #[Groups(['professional_function:read', 'professional_function:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private string $title;

    #[Groups(['professional_function:read', 'professional_function:write'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[Groups(['professional_function:read', 'professional_function:write'])]
    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: true)]
    private ?DateTimeInterface $startDate = null;

    #[Groups(['professional_function:read', 'professional_function:write'])]
    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: true)]
    private ?DateTimeInterface $endDate = null;

    /**
     * @var Collection<int, Activity>
     */
    #[Groups(['professional_function:read', 'professional_function:write'])]
    #[ORM\ManyToMany(targetEntity: Activity::class)]
    #[ORM\JoinTable(
        name: 'professional_function_activity',
        joinColumns: [new ORM\JoinColumn(name: 'professional_function_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'activity_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
    )]
    private Collection $activities;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    #[Groups(['professional_function:read'])]
    public function getParentTitle(): ?string
    {
        return $this->parent?->getTitle();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): self
    {
        if (!$this->activities->contains($activity)) {
            $this->activities->add($activity);
        }

        return $this;
    }

    public function removeActivity(Activity $activity): self
    {
        $this->activities->removeElement($activity);

        return $this;
    }
}
