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
use Chamilo\CoreBundle\Repository\JobOfferQuizRepository;
use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(paginationClientEnabled: true, security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')"),
    ],
    normalizationContext: ['groups' => ['job_offer_quiz:read']],
    denormalizationContext: ['groups' => ['job_offer_quiz:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['jobOffer' => 'exact'])]
#[ORM\Table(name: 'job_offer_quiz')]
#[ORM\Entity(repositoryClass: JobOfferQuizRepository::class)]
class JobOfferQuiz
{
    #[Groups(['job_offer_quiz:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotNull]
    #[Groups(['job_offer_quiz:read', 'job_offer_quiz:write'])]
    #[ORM\ManyToOne(targetEntity: JobOffer::class, inversedBy: 'quizzes')]
    #[ORM\JoinColumn(name: 'job_offer_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private JobOffer $jobOffer;

    #[Assert\NotNull]
    #[Groups(['job_offer_quiz:read', 'job_offer_quiz:write'])]
    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Course $course;

    #[Assert\NotNull]
    #[Groups(['job_offer_quiz:read', 'job_offer_quiz:write'])]
    #[ORM\ManyToOne(targetEntity: CQuiz::class)]
    #[ORM\JoinColumn(name: 'c_quiz_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    private CQuiz $cquiz;

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

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    #[Groups(['job_offer_quiz:read'])]
    public function getCourseTitle(): string
    {
        return (string) $this->course->getTitle();
    }

    public function getCquiz(): CQuiz
    {
        return $this->cquiz;
    }

    public function setCquiz(CQuiz $cquiz): static
    {
        $this->cquiz = $cquiz;

        return $this;
    }

    #[Groups(['job_offer_quiz:read'])]
    public function getCquizTitle(): string
    {
        return $this->cquiz->getTitle();
    }

    #[Groups(['job_offer_quiz:read', 'job_offer:public', 'job_offer_application:read'])]
    public function getExerciseUrl(): string
    {
        return '/main/exercise/overview.php?exerciseId='.$this->cquiz->getIid().'&cidReq='.$this->course->getCode();
    }
}
