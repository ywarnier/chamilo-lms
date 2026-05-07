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
use Chamilo\CoreBundle\Repository\FunctionInUnitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
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
    normalizationContext: ['groups' => ['function_in_unit:read']],
    denormalizationContext: ['groups' => ['function_in_unit:write']],
)]
#[ORM\Table(name: 'function_in_unit')]
#[ORM\Entity(repositoryClass: FunctionInUnitRepository::class)]
class FunctionInUnit
{
    #[Groups(['function_in_unit:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotNull]
    #[Groups(['function_in_unit:read', 'function_in_unit:write'])]
    #[ORM\ManyToOne(targetEntity: ProfessionalFunction::class)]
    #[ORM\JoinColumn(name: 'professional_function_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ProfessionalFunction $professionalFunction;

    #[Assert\NotNull]
    #[Groups(['function_in_unit:read', 'function_in_unit:write'])]
    #[ORM\ManyToOne(targetEntity: BusinessUnit::class)]
    #[ORM\JoinColumn(name: 'business_unit_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private BusinessUnit $businessUnit;

    #[Groups(['function_in_unit:read', 'function_in_unit:write'])]
    #[ORM\ManyToOne(targetEntity: GeographicZone::class)]
    #[ORM\JoinColumn(name: 'geographic_zone_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?GeographicZone $geographicZone = null;

    #[Assert\NotBlank]
    #[Groups(['function_in_unit:read', 'function_in_unit:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private string $title;

    #[Groups(['function_in_unit:read', 'function_in_unit:write'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Activity>
     */
    #[Groups(['function_in_unit:read', 'function_in_unit:write'])]
    #[ORM\ManyToMany(targetEntity: Activity::class)]
    #[ORM\JoinTable(
        name: 'function_in_unit_activity',
        joinColumns: [new ORM\JoinColumn(name: 'function_in_unit_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'activity_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
    )]
    private Collection $activities;

    /**
     * @var Collection<int, FunctionInUnitSkill>
     */
    #[ORM\OneToMany(mappedBy: 'functionInUnit', targetEntity: FunctionInUnitSkill::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $skills;

    /**
     * @var Collection<int, UserToFunctionInUnit>
     */
    #[ORM\OneToMany(mappedBy: 'functionInUnit', targetEntity: UserToFunctionInUnit::class, cascade: ['remove'])]
    private Collection $positions;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->positions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfessionalFunction(): ProfessionalFunction
    {
        return $this->professionalFunction;
    }

    public function setProfessionalFunction(ProfessionalFunction $professionalFunction): self
    {
        $this->professionalFunction = $professionalFunction;

        return $this;
    }

    #[Groups(['function_in_unit:read'])]
    public function getProfessionalFunctionTitle(): string
    {
        return $this->professionalFunction->getTitle();
    }

    public function getBusinessUnit(): BusinessUnit
    {
        return $this->businessUnit;
    }

    public function setBusinessUnit(BusinessUnit $businessUnit): self
    {
        $this->businessUnit = $businessUnit;

        return $this;
    }

    #[Groups(['function_in_unit:read'])]
    public function getBusinessUnitTitle(): string
    {
        return $this->businessUnit->getTitle();
    }

    public function getGeographicZone(): ?GeographicZone
    {
        return $this->geographicZone;
    }

    public function setGeographicZone(?GeographicZone $geographicZone): self
    {
        $this->geographicZone = $geographicZone;

        return $this;
    }

    #[Groups(['function_in_unit:read'])]
    public function getGeographicZoneTitle(): ?string
    {
        return $this->geographicZone?->getTitle();
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

    /**
     * @return Collection<int, FunctionInUnitSkill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(FunctionInUnitSkill $skill): self
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
            $skill->setFunctionInUnit($this);
        }

        return $this;
    }

    public function removeSkill(FunctionInUnitSkill $skill): self
    {
        $this->skills->removeElement($skill);

        return $this;
    }

    /**
     * @return Collection<int, UserToFunctionInUnit>
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }
}
