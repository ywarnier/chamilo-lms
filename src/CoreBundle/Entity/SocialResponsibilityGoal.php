<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

// Chamilo HR extension

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Repository\SocialResponsibilityGoalRepository;
use Chamilo\CoreBundle\State\SocialResponsibilityGoalPublicProvider;
use Chamilo\CoreBundle\State\SocialResponsibilityGoalStateProcessor;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(paginationClientEnabled: true, security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new GetCollection(
            uriTemplate: '/social_responsibility_goals/public.{_format}',
            openapiContext: [
                'parameters' => [
                    [
                        'name' => 'language',
                        'in' => 'query',
                        'description' => 'Locale (e.g. en_US, es_ES). Falls back to the base language if no exact match is published.',
                        'required' => false,
                        'schema' => ['type' => 'string', 'default' => 'en_US'],
                    ],
                ],
            ],
            paginationEnabled: false,
            normalizationContext: ['groups' => ['social_responsibility_goal:public']],
            security: "is_granted('PUBLIC_ACCESS')",
            provider: SocialResponsibilityGoalPublicProvider::class,
        ),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Put(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')", processor: SocialResponsibilityGoalStateProcessor::class),
    ],
    normalizationContext: ['groups' => ['social_responsibility_goal:read']],
    denormalizationContext: ['groups' => ['social_responsibility_goal:write']],
)]
#[ORM\Table(name: 'social_responsibility_goal')]
#[ORM\UniqueConstraint(name: 'sdg_language_unique', columns: ['sdg_number', 'language'])]
#[ORM\Entity(repositoryClass: SocialResponsibilityGoalRepository::class)]
#[UniqueEntity(fields: ['sdgNumber', 'language'])]
class SocialResponsibilityGoal
{
    #[Groups(['social_responsibility_goal:read', 'social_responsibility_goal:public'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Groups(['social_responsibility_goal:read', 'social_responsibility_goal:public'])]
    #[Assert\Range(min: 1, max: 17)]
    #[ORM\Column(name: 'sdg_number', type: 'integer')]
    protected int $sdgNumber;

    #[Groups(['social_responsibility_goal:read', 'social_responsibility_goal:public'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    #[ORM\Column(name: 'language', type: 'string', length: 10)]
    protected string $language;

    #[Groups(['social_responsibility_goal:read', 'social_responsibility_goal:write', 'social_responsibility_goal:public'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    protected string $title;

    #[Groups(['social_responsibility_goal:read', 'social_responsibility_goal:write', 'social_responsibility_goal:public'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[Groups(['social_responsibility_goal:read', 'social_responsibility_goal:write', 'social_responsibility_goal:public'])]
    #[ORM\Column(name: 'enforcement', type: 'text', nullable: true)]
    protected ?string $enforcement = null;

    #[Groups(['social_responsibility_goal:read', 'social_responsibility_goal:write'])]
    #[ORM\Column(name: 'is_published', type: 'boolean', options: ['default' => false])]
    protected bool $isPublished = false;

    #[Groups(['social_responsibility_goal:read'])]
    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $updatedAt = null;

    #[Groups(['social_responsibility_goal:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'updated_by', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $updatedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSdgNumber(): int
    {
        return $this->sdgNumber;
    }

    public function setSdgNumber(int $sdgNumber): self
    {
        $this->sdgNumber = $sdgNumber;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
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

    public function getEnforcement(): ?string
    {
        return $this->enforcement;
    }

    public function setEnforcement(?string $enforcement): self
    {
        $this->enforcement = $enforcement;

        return $this;
    }

    public function getIsPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}
