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
use Chamilo\CoreBundle\Repository\BenefitAssignmentRepository;
use Chamilo\CoreBundle\State\BenefitAssignmentStateProcessor;
use Chamilo\CoreBundle\State\MyBenefitsStateProvider;
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
            uriTemplate: '/me/benefit_assignments.{_format}',
            paginationClientEnabled: true,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            provider: MyBenefitsStateProvider::class,
        ),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR') or object.getUser() == user"),
        new Post(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')",
            processor: BenefitAssignmentStateProcessor::class,
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')",
            processor: BenefitAssignmentStateProcessor::class,
        ),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
    ],
    normalizationContext: ['groups' => ['benefit_assignment:read']],
    denormalizationContext: ['groups' => ['benefit_assignment:write']],
    paginationClientEnabled: true,
)]
#[ORM\Table(name: 'benefit_assignment')]
#[ORM\Entity(repositoryClass: BenefitAssignmentRepository::class)]
class BenefitAssignment
{
    #[Groups(['benefit_assignment:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotNull]
    #[Groups(['benefit_assignment:read', 'benefit_assignment:write'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected User $user;

    #[Assert\NotNull]
    #[Groups(['benefit_assignment:read', 'benefit_assignment:write'])]
    #[ORM\ManyToOne(targetEntity: Compensation::class)]
    #[ORM\JoinColumn(name: 'compensation_id', referencedColumnName: 'id', nullable: false)]
    protected Compensation $compensation;

    #[Groups(['benefit_assignment:read', 'benefit_assignment:write'])]
    #[ORM\Column(name: 'economical_equivalent', type: 'float', nullable: true)]
    protected ?float $economicalEquivalent = null;

    #[Assert\NotNull]
    #[Groups(['benefit_assignment:read', 'benefit_assignment:write'])]
    #[ORM\Column(name: 'assignment_datetime', type: 'datetime')]
    protected DateTime $assignmentDatetime;

    #[Groups(['benefit_assignment:read', 'benefit_assignment:write'])]
    #[ORM\Column(name: 'assignment_end_datetime', type: 'datetime', nullable: true)]
    protected ?DateTime $assignmentEndDatetime = null;

    #[Groups(['benefit_assignment:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assignment_author_id', referencedColumnName: 'id', nullable: false)]
    protected User $assignmentAuthor;

    #[Groups(['benefit_assignment:read', 'benefit_assignment:write'])]
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    protected ?string $comment = null;

    #[Groups(['benefit_assignment:read'])]
    #[ORM\OneToMany(
        targetEntity: BenefitNotification::class,
        mappedBy: 'benefitAssignment',
        orphanRemoval: true,
        cascade: ['persist'],
    )]
    protected Collection $notifications;

    public function __construct()
    {
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCompensation(): Compensation
    {
        return $this->compensation;
    }

    public function setCompensation(Compensation $compensation): self
    {
        $this->compensation = $compensation;

        return $this;
    }

    public function getEconomicalEquivalent(): ?float
    {
        return $this->economicalEquivalent;
    }

    public function setEconomicalEquivalent(?float $economicalEquivalent): self
    {
        $this->economicalEquivalent = $economicalEquivalent;

        return $this;
    }

    public function getAssignmentDatetime(): DateTime
    {
        return $this->assignmentDatetime;
    }

    public function setAssignmentDatetime(DateTime $assignmentDatetime): self
    {
        $this->assignmentDatetime = $assignmentDatetime;

        return $this;
    }

    public function getAssignmentEndDatetime(): ?DateTime
    {
        return $this->assignmentEndDatetime;
    }

    public function setAssignmentEndDatetime(?DateTime $assignmentEndDatetime): self
    {
        $this->assignmentEndDatetime = $assignmentEndDatetime;

        return $this;
    }

    public function getAssignmentAuthor(): User
    {
        return $this->assignmentAuthor;
    }

    public function setAssignmentAuthor(User $assignmentAuthor): self
    {
        $this->assignmentAuthor = $assignmentAuthor;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }
}
