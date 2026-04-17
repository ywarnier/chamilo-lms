<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

// Chamilo HR extension

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
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
    normalizationContext: ['groups' => ['skill_level_profile:read']],
    denormalizationContext: ['groups' => ['skill_level_profile:write']],
)]
#[ORM\Table(name: 'skill_level_profile')]
#[ORM\Entity]
class SkillLevelProfile implements Stringable
{
    #[Groups(['skill_level_profile:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['skill_level_profile:read', 'skill_level_profile:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    /**
     * @var Collection<int, Skill>
     */
    #[ORM\OneToMany(mappedBy: 'levelProfile', targetEntity: Skill::class, cascade: ['persist'])]
    protected Collection $skills;

    /**
     * @var Collection<int, Level>
     */
    #[Groups(['skill_level_profile:read'])]
    #[ORM\OneToMany(mappedBy: 'profile', targetEntity: Level::class, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $levels;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->levels = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * @return int
     */
    public function getId()
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

    /**
     * @return Skill[]|Collection
     */
    public function getSkills(): array|Collection
    {
        return $this->skills;
    }

    /**
     * @param Skill[]|Collection $skills
     */
    public function setSkills(array|Collection $skills): self
    {
        $this->skills = $skills;

        return $this;
    }

    /**
     * @return Level[]|Collection
     */
    public function getLevels(): array|Collection
    {
        return $this->levels;
    }

    /**
     * @param Collection $levels
     */
    public function setLevels($levels): self
    {
        $this->levels = $levels;

        return $this;
    }
}
