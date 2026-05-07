<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

// Chamilo HR extension

use Chamilo\CoreBundle\Repository\PerformanceAppraisalItemCommentRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'performance_appraisal_item_comment')]
#[ORM\Entity(repositoryClass: PerformanceAppraisalItemCommentRepository::class)]
class PerformanceAppraisalItemComment
{
    #[Groups(['performance_appraisal_item:read', 'performance_appraisal:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['performance_appraisal_item:read', 'performance_appraisal:read'])]
    #[ORM\Column(name: 'comment', type: 'text')]
    protected string $comment;

    #[Groups(['performance_appraisal_item:read', 'performance_appraisal:read'])]
    #[ORM\Column(name: 'created_on', type: 'datetime')]
    protected DateTime $createdOn;

    #[ORM\ManyToOne(targetEntity: PerformanceAppraisalItem::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected PerformanceAppraisalItem $item;

    #[Groups(['performance_appraisal_item:read', 'performance_appraisal:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $sender;

    public function __construct()
    {
        $this->createdOn = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedOn(): DateTime
    {
        return $this->createdOn;
    }

    public function setCreatedOn(DateTime $createdOn): self
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function getItem(): PerformanceAppraisalItem
    {
        return $this->item;
    }

    public function setItem(PerformanceAppraisalItem $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getSender(): User
    {
        return $this->sender;
    }

    public function setSender(User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }
}
