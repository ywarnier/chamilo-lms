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
use Chamilo\CoreBundle\Repository\CompensationRepository;
use Chamilo\CoreBundle\State\CompensationStateProcessor;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(paginationClientEnabled: true, security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Post(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')",
            processor: CompensationStateProcessor::class
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')",
            processor: CompensationStateProcessor::class
        ),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
    ],
    normalizationContext: ['groups' => ['compensation:read']],
    denormalizationContext: ['groups' => ['compensation:write']],
)]
#[ORM\Table(name: 'compensation')]
#[ORM\Entity(repositoryClass: CompensationRepository::class)]
class Compensation
{
    #[Groups(['compensation:read', 'benefit_assignment:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['compensation:read', 'compensation:write', 'benefit_assignment:read'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    protected string $title;

    #[Groups(['compensation:read', 'compensation:write', 'benefit_assignment:read'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[Groups(['compensation:read', 'compensation:write'])]
    #[ORM\Column(name: 'duration', type: 'string', length: 255, nullable: true)]
    protected ?string $duration = null;

    #[Assert\NotNull]
    #[Groups(['compensation:read', 'compensation:write', 'benefit_assignment:read'])]
    #[ORM\Column(name: 'score', type: 'float')]
    protected float $score = 0.0;

    #[Groups(['compensation:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: false)]
    protected User $author;

    /**
     * @var Collection<int, CompensationTag>
     */
    #[Groups(['compensation:read', 'compensation:write'])]
    #[ORM\ManyToMany(targetEntity: CompensationTag::class, inversedBy: 'compensations')]
    #[ORM\JoinTable(
        name: 'compensation_rel_tag',
        joinColumns: [new ORM\JoinColumn(name: 'compensation_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id')],
    )]
    private Collection $tags;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    protected DateTime $updatedAt;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
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

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return Collection<int, CompensationTag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(CompensationTag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(CompensationTag $tag): static
    {
        $this->tags->removeElement($tag);

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

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
