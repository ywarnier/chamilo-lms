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
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
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
    normalizationContext: ['groups' => ['skill_level:read']],
    denormalizationContext: ['groups' => ['skill_level:write']],
)]
#[ORM\Table(name: 'skill_level')]
#[ORM\Entity]
class Level implements Stringable
{
    #[Groups(['skill_level:read', 'skill_level_profile:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['skill_level:read', 'skill_level:write', 'skill_level_profile:read'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Gedmo\SortablePosition]
    #[Groups(['skill_level:read', 'skill_level:write', 'skill_level_profile:read'])]
    #[ORM\Column(name: 'position', type: 'integer')]
    protected int $position;

    #[Assert\NotBlank]
    #[Groups(['skill_level:read', 'skill_level:write', 'skill_level_profile:read'])]
    #[ORM\Column(name: 'short_title', type: 'string', length: 255, nullable: false)]
    protected string $shortTitle;

    #[Gedmo\SortableGroup]
    #[Groups(['skill_level:read', 'skill_level:write'])]
    #[ORM\ManyToOne(targetEntity: SkillLevelProfile::class, inversedBy: 'levels')]
    #[ORM\JoinColumn(name: 'profile_id', referencedColumnName: 'id')]
    protected ?SkillLevelProfile $profile = null;

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

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getShortTitle(): string
    {
        return $this->shortTitle;
    }

    public function setShortTitle(string $shortTitle): self
    {
        $this->shortTitle = $shortTitle;

        return $this;
    }

    public function getProfile(): ?SkillLevelProfile
    {
        return $this->profile;
    }

    public function setProfile(SkillLevelProfile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }
}
