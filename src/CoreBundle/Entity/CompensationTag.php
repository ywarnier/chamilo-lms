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
use Chamilo\CoreBundle\Repository\CompensationTagRepository;
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
    normalizationContext: ['groups' => ['compensation_tag:read']],
    denormalizationContext: ['groups' => ['compensation_tag:write']],
)]
#[ORM\Table(name: 'compensation_tag')]
#[ORM\Entity(repositoryClass: CompensationTagRepository::class)]
class CompensationTag
{
    #[Groups(['compensation_tag:read', 'compensation:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['compensation_tag:read', 'compensation_tag:write', 'compensation:read'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    protected string $title;

    #[Groups(['compensation_tag:read', 'compensation_tag:write'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[Assert\NotBlank]
    #[Groups(['compensation_tag:read', 'compensation_tag:write', 'compensation:read'])]
    #[ORM\Column(name: 'color', type: 'string', length: 10)]
    protected string $color = '#3B82F6';

    /**
     * @var Collection<int, Compensation>
     */
    #[ORM\ManyToMany(targetEntity: Compensation::class, mappedBy: 'tags')]
    private Collection $compensations;

    public function __construct()
    {
        $this->compensations = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection<int, Compensation>
     */
    public function getCompensations(): Collection
    {
        return $this->compensations;
    }

    public function addCompensation(Compensation $compensation): static
    {
        if (!$this->compensations->contains($compensation)) {
            $this->compensations->add($compensation);
            $compensation->addTag($this);
        }

        return $this;
    }

    public function removeCompensation(Compensation $compensation): static
    {
        if ($this->compensations->removeElement($compensation)) {
            $compensation->removeTag($this);
        }

        return $this;
    }
}
