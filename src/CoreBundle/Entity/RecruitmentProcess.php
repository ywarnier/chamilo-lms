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
use Chamilo\CoreBundle\Repository\RecruitmentProcessRepository;
use Chamilo\CoreBundle\State\RecruitmentProcessStateProcessor;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')", processor: RecruitmentProcessStateProcessor::class),
        new Put(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
    ],
    normalizationContext: ['groups' => ['recruitment_process:read']],
    denormalizationContext: ['groups' => ['recruitment_process:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['jobOffer' => 'exact', 'application' => 'exact'])]
#[ORM\Table(name: 'recruitment_process')]
#[ORM\Entity(repositoryClass: RecruitmentProcessRepository::class)]
class RecruitmentProcess
{
    #[Groups(['recruitment_process:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotNull]
    #[Groups(['recruitment_process:read', 'recruitment_process:write'])]
    #[ORM\ManyToOne(targetEntity: JobOffer::class)]
    #[ORM\JoinColumn(name: 'job_offer_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private JobOffer $jobOffer;

    #[Assert\NotNull]
    #[Groups(['recruitment_process:read', 'recruitment_process:write'])]
    #[ORM\ManyToOne(targetEntity: JobOfferApplication::class)]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private JobOfferApplication $application;

    #[Groups(['recruitment_process:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: false)]
    private User $createdBy;

    #[Groups(['recruitment_process:read', 'recruitment_process:write'])]
    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    private ?string $notes = null;

    #[Groups(['recruitment_process:read'])]
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

    public function getJobOffer(): JobOffer
    {
        return $this->jobOffer;
    }

    public function setJobOffer(JobOffer $jobOffer): static
    {
        $this->jobOffer = $jobOffer;

        return $this;
    }

    #[Groups(['recruitment_process:read'])]
    public function getJobOfferTitle(): string
    {
        return $this->jobOffer->getTitle();
    }

    public function getApplication(): JobOfferApplication
    {
        return $this->application;
    }

    public function setApplication(JobOfferApplication $application): static
    {
        $this->application = $application;

        return $this;
    }

    #[Groups(['recruitment_process:read'])]
    public function getApplicantName(): string
    {
        return $this->application->getApplicantName();
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    #[Groups(['recruitment_process:read'])]
    public function getCreatedByName(): string
    {
        return $this->createdBy->getFullname();
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
