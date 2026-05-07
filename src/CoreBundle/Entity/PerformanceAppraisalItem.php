<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

// Chamilo HR extension

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Chamilo\CoreBundle\Repository\PerformanceAppraisalItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Get(security: "is_granted('IS_AUTHENTICATED_FULLY')"),
    ],
    normalizationContext: ['groups' => ['performance_appraisal_item:read']],
    denormalizationContext: ['groups' => ['performance_appraisal_item:write']],
)]
#[ORM\Table(name: 'performance_appraisal_item')]
#[ORM\Entity(repositoryClass: PerformanceAppraisalItemRepository::class)]
class PerformanceAppraisalItem
{
    public const TYPE_SKILL = 'skill';
    public const TYPE_ACTIVITY = 'activity';
    public const TYPE_OBJECTIVE = 'objective';

    #[Groups(['performance_appraisal_item:read', 'performance_appraisal:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: [self::TYPE_SKILL, self::TYPE_ACTIVITY, self::TYPE_OBJECTIVE])]
    #[Groups(['performance_appraisal_item:read', 'performance_appraisal:read'])]
    #[ORM\Column(name: 'type', type: 'string', length: 20)]
    protected string $type;

    #[Assert\NotNull]
    #[Assert\Positive]
    #[Groups(['performance_appraisal_item:read', 'performance_appraisal:read'])]
    #[ORM\Column(name: 'ref_id', type: 'integer')]
    protected int $ref;

    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['performance_appraisal_item:read', 'performance_appraisal:read'])]
    #[ORM\Column(name: 'percentage', type: 'integer', options: ['default' => 0])]
    protected int $percentage = 0;

    #[Groups(['performance_appraisal_item:read', 'performance_appraisal_item:write', 'performance_appraisal:read'])]
    #[ORM\Column(name: 'score', type: 'integer', nullable: true)]
    protected ?int $score = null;

    #[Groups(['performance_appraisal_item:read', 'performance_appraisal_item:write', 'performance_appraisal:read'])]
    #[ORM\Column(name: 'responsible_comment', type: 'text', nullable: true)]
    protected ?string $responsibleComment = null;

    #[Groups(['performance_appraisal_item:read', 'performance_appraisal_item:write', 'performance_appraisal:read'])]
    #[ORM\Column(name: 'collaborator_comment', type: 'text', nullable: true)]
    protected ?string $collaboratorComment = null;

    #[ORM\ManyToOne(targetEntity: PerformanceAppraisal::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'appraisal_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected PerformanceAppraisal $appraisal;

    /**
     * @var Collection<int, PerformanceAppraisalItemComment>
     */
    #[Groups(['performance_appraisal_item:read', 'performance_appraisal:read'])]
    #[ORM\OneToMany(
        mappedBy: 'item',
        targetEntity: PerformanceAppraisalItemComment::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['createdOn' => 'ASC'])]
    protected Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getRef(): int
    {
        return $this->ref;
    }

    public function setRef(int $ref): self
    {
        $this->ref = $ref;

        return $this;
    }

    public function getPercentage(): int
    {
        return $this->percentage;
    }

    public function setPercentage(int $percentage): self
    {
        $this->percentage = $percentage;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getResponsibleComment(): ?string
    {
        return $this->responsibleComment;
    }

    public function setResponsibleComment(?string $responsibleComment): self
    {
        $this->responsibleComment = $responsibleComment;

        return $this;
    }

    public function getCollaboratorComment(): ?string
    {
        return $this->collaboratorComment;
    }

    public function setCollaboratorComment(?string $collaboratorComment): self
    {
        $this->collaboratorComment = $collaboratorComment;

        return $this;
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

    /**
     * @return Collection<int, PerformanceAppraisalItemComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(PerformanceAppraisalItemComment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setItem($this);
        }

        return $this;
    }

    public function removeComment(PerformanceAppraisalItemComment $comment): self
    {
        $this->comments->removeElement($comment);

        return $this;
    }

    public function calcScore(): float
    {
        if (null === $this->score || 0 === $this->percentage) {
            return 0.0;
        }

        return $this->score * $this->percentage / 100.0;
    }
}
