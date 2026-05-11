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
use Chamilo\CoreBundle\Repository\JobOfferApplicationRepository;
use Chamilo\CoreBundle\State\JobOfferApplicationStateProcessor;
use Chamilo\CoreBundle\State\JobOfferMyApplicationsProvider;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new GetCollection(
            uriTemplate: '/job_offer_applications/mine',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            provider: JobOfferMyApplicationsProvider::class,
            paginationEnabled: false,
        ),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR') or object.getCreatedBy() == user"),
        new Post(security: "is_granted('IS_AUTHENTICATED_FULLY')", processor: JobOfferApplicationStateProcessor::class),
        new Put(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')", processor: JobOfferApplicationStateProcessor::class),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR') or (is_granted('IS_AUTHENTICATED_FULLY') and object.getCreatedBy() == user and object.getHired() == 0)"),
    ],
    normalizationContext: ['groups' => ['job_offer_application:read']],
    denormalizationContext: ['groups' => ['job_offer_application:write']],
    paginationClientEnabled: true,
)]
#[ApiFilter(SearchFilter::class, properties: ['jobOffer' => 'exact'])]
#[ORM\Table(name: 'job_offer_application')]
#[ORM\Entity(repositoryClass: JobOfferApplicationRepository::class)]
class JobOfferApplication
{
    public const STATUS_STAND_BY = 0;
    public const STATUS_HIRED = 1;
    public const STATUS_NOT_HIRED = 2;

    #[Groups(['job_offer_application:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotNull]
    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\ManyToOne(targetEntity: JobOffer::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(name: 'job_offer_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private JobOffer $jobOffer;

    #[Groups(['job_offer_application:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: false)]
    private User $createdBy;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\ManyToOne(targetEntity: PersonalFile::class)]
    #[ORM\JoinColumn(name: 'cv_personal_file_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PersonalFile $cvFile = null;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\ManyToOne(targetEntity: PersonalFile::class)]
    #[ORM\JoinColumn(name: 'motivation_letter_personal_file_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PersonalFile $motivationLetterFile = null;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\Column(name: 'introduction', type: 'string', length: 255, nullable: true)]
    private ?string $introduction = null;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\Column(name: 'salary_expectations', type: 'string', length: 255, nullable: true)]
    private ?string $salaryExpectations = null;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\Column(name: 'availability_date', type: 'datetime', nullable: true)]
    private ?DateTimeInterface $availabilityDate = null;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\Column(name: 'hired', type: 'integer', options: ['default' => 0])]
    private int $hired = self::STATUS_STAND_BY;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\Column(name: 'cv_observation', type: 'text', nullable: true)]
    private ?string $cvObservation = null;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\Column(name: 'cv_private_observation', type: 'text', nullable: true)]
    private ?string $cvPrivateObservation = null;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\Column(name: 'motivation_letter_observation', type: 'text', nullable: true)]
    private ?string $motivationLetterObservation = null;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\Column(name: 'motivation_letter_private_observation', type: 'text', nullable: true)]
    private ?string $motivationLetterPrivateObservation = null;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\Column(name: 'observation', type: 'text', nullable: true)]
    private ?string $observation = null;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\Column(name: 'private_observation', type: 'text', nullable: true)]
    private ?string $privateObservation = null;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\ManyToOne(targetEntity: PersonalFile::class)]
    #[ORM\JoinColumn(name: 'contract_personal_file_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PersonalFile $contractFile = null;

    #[Groups(['job_offer_application:read'])]
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private DateTimeInterface $createdAt;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\Column(name: 'total_score', type: 'float', nullable: true)]
    private ?float $totalScore = null;

    #[Groups(['job_offer_application:read', 'job_offer_application:write'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'evaluated_by', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $evaluatedBy = null;

    #[Groups(['job_offer_application:read'])]
    #[ORM\Column(name: 'first_evaluated_at', type: 'datetime', nullable: true)]
    private ?DateTimeInterface $firstEvaluatedAt = null;

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

    #[Groups(['job_offer_application:read'])]
    public function getJobOfferTitle(): string
    {
        return $this->jobOffer->getTitle();
    }

    /**
     * @return array<int, array{title: string, courseTitle: string, exerciseUrl: string}>
     */
    #[Groups(['job_offer_application:read'])]
    public function getLinkedQuizzes(): array
    {
        return $this->jobOffer->getSelectionTests();
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

    #[Groups(['job_offer_application:read'])]
    public function getApplicantName(): string
    {
        return $this->createdBy->getFullname();
    }

    #[Groups(['job_offer_application:read'])]
    public function getCreatedById(): ?int
    {
        return $this->createdBy->getId();
    }

    #[Groups(['job_offer_application:read'])]
    public function getCvFileTitle(): ?string
    {
        return $this->cvFile?->getTitle();
    }

    #[Groups(['job_offer_application:read'])]
    public function getMotivationLetterFileTitle(): ?string
    {
        return $this->motivationLetterFile?->getTitle();
    }

    public function getCvFile(): ?PersonalFile
    {
        return $this->cvFile;
    }

    public function setCvFile(?PersonalFile $cvFile): static
    {
        $this->cvFile = $cvFile;

        return $this;
    }

    public function getMotivationLetterFile(): ?PersonalFile
    {
        return $this->motivationLetterFile;
    }

    public function setMotivationLetterFile(?PersonalFile $motivationLetterFile): static
    {
        $this->motivationLetterFile = $motivationLetterFile;

        return $this;
    }

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(?string $introduction): static
    {
        $this->introduction = $introduction;

        return $this;
    }

    public function getSalaryExpectations(): ?string
    {
        return $this->salaryExpectations;
    }

    public function setSalaryExpectations(?string $salaryExpectations): static
    {
        $this->salaryExpectations = $salaryExpectations;

        return $this;
    }

    public function getAvailabilityDate(): ?DateTimeInterface
    {
        return $this->availabilityDate;
    }

    public function setAvailabilityDate(?DateTimeInterface $availabilityDate): static
    {
        $this->availabilityDate = $availabilityDate;

        return $this;
    }

    public function getHired(): int
    {
        return $this->hired;
    }

    public function setHired(int $hired): static
    {
        $this->hired = $hired;

        return $this;
    }

    public function getCvObservation(): ?string
    {
        return $this->cvObservation;
    }

    public function setCvObservation(?string $cvObservation): static
    {
        $this->cvObservation = $cvObservation;

        return $this;
    }

    public function getCvPrivateObservation(): ?string
    {
        return $this->cvPrivateObservation;
    }

    public function setCvPrivateObservation(?string $cvPrivateObservation): static
    {
        $this->cvPrivateObservation = $cvPrivateObservation;

        return $this;
    }

    public function getMotivationLetterObservation(): ?string
    {
        return $this->motivationLetterObservation;
    }

    public function setMotivationLetterObservation(?string $motivationLetterObservation): static
    {
        $this->motivationLetterObservation = $motivationLetterObservation;

        return $this;
    }

    public function getMotivationLetterPrivateObservation(): ?string
    {
        return $this->motivationLetterPrivateObservation;
    }

    public function setMotivationLetterPrivateObservation(?string $motivationLetterPrivateObservation): static
    {
        $this->motivationLetterPrivateObservation = $motivationLetterPrivateObservation;

        return $this;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): static
    {
        $this->observation = $observation;

        return $this;
    }

    public function getPrivateObservation(): ?string
    {
        return $this->privateObservation;
    }

    public function setPrivateObservation(?string $privateObservation): static
    {
        $this->privateObservation = $privateObservation;

        return $this;
    }

    public function getContractFile(): ?PersonalFile
    {
        return $this->contractFile;
    }

    public function setContractFile(?PersonalFile $contractFile): static
    {
        $this->contractFile = $contractFile;

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

    public function getTotalScore(): ?float
    {
        return $this->totalScore;
    }

    public function setTotalScore(?float $totalScore): static
    {
        $this->totalScore = $totalScore;

        return $this;
    }

    public function getEvaluatedBy(): ?User
    {
        return $this->evaluatedBy;
    }

    #[Groups(['job_offer_application:read'])]
    public function getEvaluatedByName(): ?string
    {
        return $this->evaluatedBy?->getFullname();
    }

    public function setEvaluatedBy(?User $evaluatedBy): static
    {
        $this->evaluatedBy = $evaluatedBy;

        return $this;
    }

    public function getFirstEvaluatedAt(): ?DateTimeInterface
    {
        return $this->firstEvaluatedAt;
    }

    public function setFirstEvaluatedAt(?DateTimeInterface $firstEvaluatedAt): static
    {
        $this->firstEvaluatedAt = $firstEvaluatedAt;

        return $this;
    }
}
