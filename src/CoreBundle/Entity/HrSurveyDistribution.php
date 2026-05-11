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
use Chamilo\CoreBundle\ApiResource\HrSurveyResultsItem;
use Chamilo\CoreBundle\Repository\HrSurveyDistributionRepository;
use Chamilo\CoreBundle\State\HrSurveyDistributionProcessor;
use Chamilo\CoreBundle\State\HrSurveyResultsStateProvider;
use Chamilo\CourseBundle\Entity\CSurvey;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        // POST also dispatches CSurveyInvitation rows + in-platform notifications
        // to every member of the target BusinessUnit. See HrSurveyDistributionProcessor.
        new Post(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')",
            processor: HrSurveyDistributionProcessor::class,
        ),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        // Aggregated results for the distribution (per-question response counts).
        new Get(
            uriTemplate: '/hr_survey_distributions/{id}/results',
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')",
            normalizationContext: ['groups' => ['hr_survey_results:read']],
            output: HrSurveyResultsItem::class,
            name: 'hr_survey_distribution_results',
            provider: HrSurveyResultsStateProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['hr_survey_distribution:read']],
    denormalizationContext: ['groups' => ['hr_survey_distribution:write']],
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['category' => 'exact', 'businessUnit' => 'exact'])]
#[ORM\Table(name: 'hr_survey_distribution')]
#[ORM\Entity(repositoryClass: HrSurveyDistributionRepository::class)]
class HrSurveyDistribution
{
    use TimestampableEntity;

    #[Groups(['hr_survey_distribution:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotNull]
    #[Groups(['hr_survey_distribution:read', 'hr_survey_distribution:write'])]
    #[ORM\ManyToOne(targetEntity: CSurvey::class)]
    #[ORM\JoinColumn(name: 'survey_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    private CSurvey $survey;

    #[Assert\NotNull]
    #[Groups(['hr_survey_distribution:read', 'hr_survey_distribution:write'])]
    #[ORM\ManyToOne(targetEntity: BusinessUnit::class)]
    #[ORM\JoinColumn(name: 'business_unit_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private BusinessUnit $businessUnit;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['training_need', 'work_climate'])]
    #[Groups(['hr_survey_distribution:read', 'hr_survey_distribution:write'])]
    #[ORM\Column(name: 'category', type: 'string', length: 32)]
    private string $category;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSurvey(): CSurvey
    {
        return $this->survey;
    }

    public function setSurvey(CSurvey $survey): self
    {
        $this->survey = $survey;

        return $this;
    }

    #[Groups(['hr_survey_distribution:read'])]
    public function getSurveyTitle(): string
    {
        return $this->survey->getTitle();
    }

    public function getBusinessUnit(): BusinessUnit
    {
        return $this->businessUnit;
    }

    public function setBusinessUnit(BusinessUnit $businessUnit): self
    {
        $this->businessUnit = $businessUnit;

        return $this;
    }

    #[Groups(['hr_survey_distribution:read'])]
    public function getBusinessUnitTitle(): string
    {
        return $this->businessUnit->getTitle();
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }
}
