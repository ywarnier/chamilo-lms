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
use Chamilo\CoreBundle\Repository\JobOfferRepository;
use Chamilo\CoreBundle\State\JobOfferPublicProvider;
use Chamilo\CoreBundle\State\JobOfferStateProcessor;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new GetCollection(
            uriTemplate: '/job_offers/public.{_format}',
            paginationEnabled: false,
            normalizationContext: ['groups' => ['job_offer:public']],
            provider: JobOfferPublicProvider::class,
        ),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR') or object.getIsPublic() == true"),
        new Post(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')",
            processor: JobOfferStateProcessor::class
        ),
        new Put(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
    ],
    normalizationContext: ['groups' => ['job_offer:read']],
    denormalizationContext: ['groups' => ['job_offer:write']],
    paginationClientEnabled: true,
)]
#[ORM\Table(name: 'job_offer')]
#[ORM\Entity(repositoryClass: JobOfferRepository::class)]
class JobOffer
{
    #[Groups(['job_offer:read', 'job_offer:public'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['job_offer:read', 'job_offer:write', 'job_offer:public'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private string $title;

    #[Assert\NotBlank]
    #[Groups(['job_offer:read', 'job_offer:write', 'job_offer:public'])]
    #[ORM\Column(name: 'description', type: 'text')]
    private string $description;

    #[Assert\NotNull]
    #[Groups(['job_offer:read', 'job_offer:write', 'job_offer:public'])]
    #[ORM\ManyToOne(targetEntity: FunctionInUnit::class)]
    #[ORM\JoinColumn(name: 'function_in_unit_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private FunctionInUnit $functionInUnit;

    #[Groups(['job_offer:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: false)]
    private User $createdBy;

    #[Assert\NotNull]
    #[Groups(['job_offer:read', 'job_offer:write', 'job_offer:public'])]
    #[ORM\Column(name: 'vacancy', type: 'integer')]
    private int $vacancy = 1;

    #[Groups(['job_offer:read', 'job_offer:write', 'job_offer:public'])]
    #[ORM\Column(name: 'salary', type: 'string', length: 255, nullable: true)]
    private ?string $salary = null;

    #[Groups(['job_offer:read', 'job_offer:write', 'job_offer:public'])]
    #[ORM\Column(name: 'expected_start_date', type: 'date', nullable: true)]
    private ?DateTimeInterface $expectedStartDate = null;

    #[Groups(['job_offer:read', 'job_offer:write'])]
    #[ORM\Column(name: 'contract_duration', type: 'integer', nullable: true)]
    private ?int $contractDuration = null;

    #[Groups(['job_offer:read', 'job_offer:write', 'job_offer:public'])]
    #[ORM\ManyToOne(targetEntity: ContractType::class)]
    #[ORM\JoinColumn(name: 'contract_type_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ContractType $contractType = null;

    #[Groups(['job_offer:read', 'job_offer:write', 'job_offer:public'])]
    #[ORM\Column(name: 'is_public', type: 'boolean', options: ['default' => false])]
    private bool $isPublic = false;

    #[Groups(['job_offer:read', 'job_offer:write', 'job_offer:public'])]
    #[ORM\Column(name: 'publication_start_date', type: 'datetime', nullable: true)]
    private ?DateTimeInterface $publicationStartDate = null;

    #[Groups(['job_offer:read', 'job_offer:write', 'job_offer:public'])]
    #[ORM\Column(name: 'publication_end_date', type: 'datetime', nullable: true)]
    private ?DateTimeInterface $publicationEndDate = null;

    #[Groups(['job_offer:read'])]
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private DateTimeInterface $createdAt;

    /**
     * @var Collection<int, JobOfferApplication>
     */
    #[ORM\OneToMany(mappedBy: 'jobOffer', targetEntity: JobOfferApplication::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $applications;

    /**
     * @var Collection<int, JobOfferQuiz>
     */
    #[ORM\OneToMany(mappedBy: 'jobOffer', targetEntity: JobOfferQuiz::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $quizzes;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->applications = new ArrayCollection();
        $this->quizzes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getFunctionInUnit(): FunctionInUnit
    {
        return $this->functionInUnit;
    }

    public function setFunctionInUnit(FunctionInUnit $functionInUnit): static
    {
        $this->functionInUnit = $functionInUnit;

        return $this;
    }

    #[Groups(['job_offer:read', 'job_offer:public'])]
    public function getFunctionInUnitTitle(): string
    {
        return $this->functionInUnit->getTitle();
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

    #[Groups(['job_offer:read'])]
    public function getCreatedByName(): string
    {
        return $this->createdBy->getFullname();
    }

    /**
     * @return array<int, array{skillId: int, skillTitle: string, levelTitle: string|null}>
     */
    #[Groups(['job_offer:read', 'job_offer:public'])]
    public function getRequiredSkills(): array
    {
        $result = [];
        foreach ($this->functionInUnit->getSkills() as $skill) {
            $result[] = [
                'skillId' => $skill->getSkill()->getId(),
                'skillTitle' => $skill->getSkillTitle(),
                'levelTitle' => $skill->getLevelTitle(),
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{title: string, courseTitle: string, exerciseUrl: string}>
     */
    #[Groups(['job_offer:read', 'job_offer:public'])]
    public function getSelectionTests(): array
    {
        $result = [];
        foreach ($this->quizzes as $quiz) {
            $result[] = [
                'title' => $quiz->getCquizTitle(),
                'courseTitle' => $quiz->getCourseTitle(),
                'exerciseUrl' => $quiz->getExerciseUrl(),
            ];
        }

        return $result;
    }

    public function getVacancy(): int
    {
        return $this->vacancy;
    }

    public function setVacancy(int $vacancy): static
    {
        $this->vacancy = $vacancy;

        return $this;
    }

    public function getSalary(): ?string
    {
        return $this->salary;
    }

    public function setSalary(?string $salary): static
    {
        $this->salary = $salary;

        return $this;
    }

    public function getExpectedStartDate(): ?DateTimeInterface
    {
        return $this->expectedStartDate;
    }

    public function setExpectedStartDate(?DateTimeInterface $expectedStartDate): static
    {
        $this->expectedStartDate = $expectedStartDate;

        return $this;
    }

    public function getContractDuration(): ?int
    {
        return $this->contractDuration;
    }

    public function setContractDuration(?int $contractDuration): static
    {
        $this->contractDuration = $contractDuration;

        return $this;
    }

    public function getContractType(): ?ContractType
    {
        return $this->contractType;
    }

    public function setContractType(?ContractType $contractType): static
    {
        $this->contractType = $contractType;

        return $this;
    }

    #[Groups(['job_offer:read', 'job_offer:public'])]
    public function getContractTypeTitle(): ?string
    {
        return $this->contractType?->getTitle();
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function getIsPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getPublicationStartDate(): ?DateTimeInterface
    {
        return $this->publicationStartDate;
    }

    public function setPublicationStartDate(?DateTimeInterface $publicationStartDate): static
    {
        $this->publicationStartDate = $publicationStartDate;

        return $this;
    }

    public function getPublicationEndDate(): ?DateTimeInterface
    {
        return $this->publicationEndDate;
    }

    public function setPublicationEndDate(?DateTimeInterface $publicationEndDate): static
    {
        $this->publicationEndDate = $publicationEndDate;

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

    /**
     * @return Collection<int, JobOfferApplication>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    /**
     * @return Collection<int, JobOfferQuiz>
     */
    public function getQuizzes(): Collection
    {
        return $this->quizzes;
    }

    #[Groups(['job_offer:read', 'job_offer:public'])]
    public function getApplicationCount(): int
    {
        return $this->applications->count();
    }
}
