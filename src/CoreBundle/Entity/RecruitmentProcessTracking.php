<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

// Chamilo HR extension

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Repository\RecruitmentProcessTrackingRepository;
use DateTime;
use DateTimeInterface;
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
    normalizationContext: ['groups' => ['recruitment_tracking:read']],
    denormalizationContext: ['groups' => ['recruitment_tracking:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['process' => 'exact'])]
#[ORM\Table(name: 'recruitment_process_tracking')]
#[ORM\Entity(repositoryClass: RecruitmentProcessTrackingRepository::class)]
class RecruitmentProcessTracking
{
    #[Groups(['recruitment_tracking:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotNull]
    #[Groups(['recruitment_tracking:read', 'recruitment_tracking:write'])]
    #[ORM\ManyToOne(targetEntity: RecruitmentProcess::class)]
    #[ORM\JoinColumn(name: 'process_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RecruitmentProcess $process;

    #[Assert\NotNull]
    #[Groups(['recruitment_tracking:read', 'recruitment_tracking:write'])]
    #[ORM\ManyToOne(targetEntity: RecruitmentStage::class)]
    #[ORM\JoinColumn(name: 'stage_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private RecruitmentStage $stage;

    #[Groups(['recruitment_tracking:read', 'recruitment_tracking:write'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'supervisor_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $supervisor = null;

    #[Groups(['recruitment_tracking:read', 'recruitment_tracking:write'])]
    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    private ?string $notes = null;

    #[Groups(['recruitment_tracking:read'])]
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProcess(): RecruitmentProcess
    {
        return $this->process;
    }

    public function setProcess(RecruitmentProcess $process): static
    {
        $this->process = $process;

        return $this;
    }

    public function getStage(): RecruitmentStage
    {
        return $this->stage;
    }

    public function setStage(RecruitmentStage $stage): static
    {
        $this->stage = $stage;

        return $this;
    }

    #[Groups(['recruitment_tracking:read'])]
    public function getStageTitle(): string
    {
        return $this->stage->getTitle();
    }

    public function getSupervisor(): ?User
    {
        return $this->supervisor;
    }

    public function setSupervisor(?User $supervisor): static
    {
        $this->supervisor = $supervisor;

        return $this;
    }

    #[Groups(['recruitment_tracking:read'])]
    public function getSupervisorName(): ?string
    {
        return $this->supervisor?->getFullname();
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
