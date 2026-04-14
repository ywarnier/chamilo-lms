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
use Chamilo\CoreBundle\Repository\PerformanceObjectiveCategoryRepository;
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
    normalizationContext: ['groups' => ['performance_objective_category:read']],
    denormalizationContext: ['groups' => ['performance_objective_category:write']],
)]
#[ORM\Table(name: 'performance_objective_category')]
#[ORM\Entity(repositoryClass: PerformanceObjectiveCategoryRepository::class)]
class PerformanceObjectiveCategory
{
    #[Groups(['performance_objective_category:read', 'performance_objective:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['performance_objective_category:read', 'performance_objective_category:write', 'performance_objective:read'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    protected string $title;

    #[Groups(['performance_objective_category:read', 'performance_objective_category:write'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

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
}
