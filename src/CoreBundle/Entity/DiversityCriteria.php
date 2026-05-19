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
use Chamilo\CoreBundle\Repository\DiversityCriteriaRepository;
use Chamilo\CoreBundle\State\DiversityCriteriaStateProcessor;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(paginationClientEnabled: true, security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Post(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')",
            processor: DiversityCriteriaStateProcessor::class,
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')",
            processor: DiversityCriteriaStateProcessor::class,
        ),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
    ],
    normalizationContext: ['groups' => ['diversity_criteria:read']],
    denormalizationContext: ['groups' => ['diversity_criteria:write']],
)]
#[ORM\Table(name: 'diversity_criteria')]
#[ORM\Entity(repositoryClass: DiversityCriteriaRepository::class)]
class DiversityCriteria
{
    #[Groups(['diversity_criteria:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['diversity_criteria:read', 'diversity_criteria:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private string $title;

    #[Groups(['diversity_criteria:read', 'diversity_criteria:write'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[Assert\NotNull]
    #[Groups(['diversity_criteria:read', 'diversity_criteria:write'])]
    #[ORM\ManyToOne(targetEntity: ExtraField::class)]
    #[ORM\JoinColumn(name: 'extra_field_id', referencedColumnName: 'id', nullable: false)]
    private ExtraField $extraField;

    #[Groups(['diversity_criteria:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: false)]
    private User $createdBy;

    #[Groups(['diversity_criteria:read'])]
    #[ORM\Column(name: 'created_on', type: 'datetime')]
    private DateTime $createdOn;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'updated_by', referencedColumnName: 'id', nullable: true)]
    private ?User $updatedBy = null;

    #[ORM\Column(name: 'updated_on', type: 'datetime', nullable: true)]
    private ?DateTime $updatedOn = null;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getExtraField(): ExtraField
    {
        return $this->extraField;
    }

    public function setExtraField(ExtraField $extraField): self
    {
        $this->extraField = $extraField;

        return $this;
    }

    #[Groups(['diversity_criteria:read'])]
    public function getExtraFieldDisplayText(): ?string
    {
        return $this->extraField->getDisplayText();
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedOn(): DateTime
    {
        return $this->createdOn;
    }

    public function setCreatedOn(DateTime $createdOn): self
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedOn(): ?DateTime
    {
        return $this->updatedOn;
    }

    public function setUpdatedOn(?DateTime $updatedOn): self
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }
}
