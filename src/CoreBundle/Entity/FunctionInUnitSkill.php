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
use Chamilo\CoreBundle\Repository\FunctionInUnitSkillRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiFilter(SearchFilter::class, properties: ['functionInUnit' => 'exact'])]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['function_in_unit_skill:read']],
    denormalizationContext: ['groups' => ['function_in_unit_skill:write']],
)]
#[ORM\Table(name: 'function_in_unit_skill')]
#[ORM\Entity(repositoryClass: FunctionInUnitSkillRepository::class)]
class FunctionInUnitSkill
{
    #[Groups(['function_in_unit_skill:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotNull]
    #[Groups(['function_in_unit_skill:write'])]
    #[ORM\ManyToOne(targetEntity: FunctionInUnit::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(name: 'function_in_unit_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private FunctionInUnit $functionInUnit;

    #[Assert\NotNull]
    #[Groups(['function_in_unit_skill:read', 'function_in_unit_skill:write'])]
    #[ORM\ManyToOne(targetEntity: Skill::class)]
    #[ORM\JoinColumn(name: 'skill_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Skill $skill;

    #[Groups(['function_in_unit_skill:read', 'function_in_unit_skill:write'])]
    #[ORM\ManyToOne(targetEntity: Level::class)]
    #[ORM\JoinColumn(name: 'level_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Level $level = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSkill(): Skill
    {
        return $this->skill;
    }

    public function setSkill(Skill $skill): self
    {
        $this->skill = $skill;

        return $this;
    }

    #[Groups(['function_in_unit_skill:read'])]
    public function getSkillTitle(): string
    {
        return $this->skill->getTitle();
    }

    public function getLevel(): ?Level
    {
        return $this->level;
    }

    public function setLevel(?Level $level): self
    {
        $this->level = $level;

        return $this;
    }

    #[Groups(['function_in_unit_skill:read'])]
    public function getLevelTitle(): ?string
    {
        return $this->level?->getTitle();
    }
}
