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
use Chamilo\CoreBundle\Repository\BusinessUnitRepository;
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
    normalizationContext: ['groups' => ['business_unit:read']],
    denormalizationContext: ['groups' => ['business_unit:write']],
)]
#[ORM\Table(name: 'business_unit')]
#[ORM\Entity(repositoryClass: BusinessUnitRepository::class)]
class BusinessUnit
{
    #[Groups(['business_unit:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Groups(['business_unit:write'])]
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    #[Groups(['business_unit:write'])]
    #[ORM\ManyToOne(targetEntity: BranchSync::class)]
    #[ORM\JoinColumn(name: 'branch_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?BranchSync $branch = null;

    #[Assert\NotBlank]
    #[Groups(['business_unit:read', 'business_unit:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private string $title;

    #[Groups(['business_unit:read', 'business_unit:write'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

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

    #[Groups(['business_unit:read'])]
    public function getParentIri(): ?string
    {
        return null !== $this->parent ? '/api/business_units/'.$this->parent->getId() : null;
    }

    #[Groups(['business_unit:read'])]
    public function getParentTitle(): ?string
    {
        return $this->parent?->getTitle();
    }

    public function getBranch(): ?BranchSync
    {
        return $this->branch;
    }

    public function setBranch(?BranchSync $branch): self
    {
        $this->branch = $branch;

        return $this;
    }

    #[Groups(['business_unit:read'])]
    public function getBranchIri(): ?string
    {
        return null !== $this->branch ? '/api/branches/'.$this->branch->getId() : null;
    }

    #[Groups(['business_unit:read'])]
    public function getBranchTitle(): ?string
    {
        return $this->branch?->getTitle();
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
}
