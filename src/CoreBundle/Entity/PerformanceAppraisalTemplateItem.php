<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

// Chamilo HR extension

use Chamilo\CoreBundle\Repository\PerformanceAppraisalTemplateItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'performance_appraisal_template_item')]
#[ORM\Entity(repositoryClass: PerformanceAppraisalTemplateItemRepository::class)]
class PerformanceAppraisalTemplateItem
{
    public const TYPE_SKILL = 'skill';
    public const TYPE_ACTIVITY = 'activity';
    public const TYPE_OBJECTIVE = 'objective';

    #[Groups(['performance_appraisal_template:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: [self::TYPE_SKILL, self::TYPE_ACTIVITY, self::TYPE_OBJECTIVE])]
    #[Groups(['performance_appraisal_template:read', 'performance_appraisal_template:write'])]
    #[ORM\Column(name: 'type', type: 'string', length: 20)]
    protected string $type;

    #[Assert\NotNull]
    #[Assert\Positive]
    #[Groups(['performance_appraisal_template:read', 'performance_appraisal_template:write'])]
    #[ORM\Column(name: 'ref_id', type: 'integer')]
    protected int $ref;

    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['performance_appraisal_template:read', 'performance_appraisal_template:write'])]
    #[ORM\Column(name: 'percentage', type: 'integer', options: ['default' => 0])]
    protected int $percentage = 0;

    #[ORM\ManyToOne(targetEntity: PerformanceAppraisalTemplate::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected PerformanceAppraisalTemplate $template;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getRef(): int
    {
        return $this->ref;
    }

    public function setRef(int $ref): self
    {
        $this->ref = $ref;

        return $this;
    }

    public function getPercentage(): int
    {
        return $this->percentage;
    }

    public function setPercentage(int $percentage): self
    {
        $this->percentage = $percentage;

        return $this;
    }

    public function getTemplate(): PerformanceAppraisalTemplate
    {
        return $this->template;
    }

    public function setTemplate(PerformanceAppraisalTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }
}
