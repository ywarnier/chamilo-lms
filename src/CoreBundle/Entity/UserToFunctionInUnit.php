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
use Chamilo\CoreBundle\Repository\UserToFunctionInUnitRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['user_to_function_in_unit:read']],
    denormalizationContext: ['groups' => ['user_to_function_in_unit:write']],
)]
#[ORM\Table(name: 'user_to_function_in_unit')]
#[ORM\Entity(repositoryClass: UserToFunctionInUnitRepository::class)]
class UserToFunctionInUnit
{
    #[Groups(['user_to_function_in_unit:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotNull]
    #[Groups(['user_to_function_in_unit:read', 'user_to_function_in_unit:write'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[Assert\NotNull]
    #[Groups(['user_to_function_in_unit:read', 'user_to_function_in_unit:write'])]
    #[ORM\ManyToOne(targetEntity: FunctionInUnit::class, inversedBy: 'positions')]
    #[ORM\JoinColumn(name: 'function_in_unit_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private FunctionInUnit $functionInUnit;

    #[Groups(['user_to_function_in_unit:read', 'user_to_function_in_unit:write'])]
    #[ORM\ManyToOne(targetEntity: BranchSync::class)]
    #[ORM\JoinColumn(name: 'branch_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?BranchSync $branch = null;

    #[Groups(['user_to_function_in_unit:read', 'user_to_function_in_unit:write'])]
    #[ORM\ManyToOne(targetEntity: GeographicZone::class)]
    #[ORM\JoinColumn(name: 'geographic_zone_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?GeographicZone $geographicZone = null;

    #[Assert\NotNull]
    #[Groups(['user_to_function_in_unit:read', 'user_to_function_in_unit:write'])]
    #[ORM\Column(name: 'start_date', type: 'datetime')]
    private DateTimeInterface $startDate;

    #[Groups(['user_to_function_in_unit:read', 'user_to_function_in_unit:write'])]
    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: true)]
    private ?DateTimeInterface $endDate = null;

    #[Groups(['user_to_function_in_unit:read', 'user_to_function_in_unit:write'])]
    #[ORM\Column(name: 'is_boss', type: 'boolean', options: ['default' => false])]
    private bool $isBoss = false;

    #[Groups(['user_to_function_in_unit:read', 'user_to_function_in_unit:write'])]
    #[ORM\Column(name: 'contract_etp', type: 'decimal', precision: 3, scale: 2, nullable: true)]
    private ?string $contractEtp = null;

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

    #[Groups(['user_to_function_in_unit:read'])]
    public function getUserFullName(): string
    {
        return $this->user->getFullName();
    }

    #[Groups(['user_to_function_in_unit:read'])]
    public function getUserUsername(): string
    {
        return $this->user->getUsername();
    }

    public function getFunctionInUnit(): FunctionInUnit
    {
        return $this->functionInUnit;
    }

    public function setFunctionInUnit(FunctionInUnit $functionInUnit): self
    {
        $this->functionInUnit = $functionInUnit;

        return $this;
    }

    #[Groups(['user_to_function_in_unit:read'])]
    public function getFunctionInUnitTitle(): string
    {
        return $this->functionInUnit->getTitle();
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

    #[Groups(['user_to_function_in_unit:read'])]
    public function getBranchTitle(): ?string
    {
        return $this->branch?->getTitle();
    }

    public function getGeographicZone(): ?GeographicZone
    {
        return $this->geographicZone;
    }

    public function setGeographicZone(?GeographicZone $geographicZone): self
    {
        $this->geographicZone = $geographicZone;

        return $this;
    }

    #[Groups(['user_to_function_in_unit:read'])]
    public function getGeographicZoneTitle(): ?string
    {
        return $this->geographicZone?->getTitle();
    }

    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function isBoss(): bool
    {
        return $this->isBoss;
    }

    public function setIsBoss(bool $isBoss): self
    {
        $this->isBoss = $isBoss;

        return $this;
    }

    public function getContractEtp(): ?string
    {
        return $this->contractEtp;
    }

    public function setContractEtp(?string $contractEtp): self
    {
        $this->contractEtp = $contractEtp;

        return $this;
    }
}
