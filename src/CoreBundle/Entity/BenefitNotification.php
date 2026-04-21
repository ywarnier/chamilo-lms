<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

// Chamilo HR extension

use Chamilo\CoreBundle\Repository\BenefitNotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table(name: 'benefit_notification')]
#[ORM\Entity(repositoryClass: BenefitNotificationRepository::class)]
class BenefitNotification
{
    #[Groups(['benefit_assignment:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BenefitAssignment::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(name: 'benefit_assignment_id', referencedColumnName: 'id', nullable: false)]
    protected BenefitAssignment $benefitAssignment;

    #[Groups(['benefit_assignment:read'])]
    #[ORM\Column(name: 'subject', type: 'string', length: 255)]
    protected string $subject;

    #[Groups(['benefit_assignment:read'])]
    #[ORM\Column(name: 'message', type: 'text')]
    protected string $message;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'id', nullable: false)]
    protected User $sender;

    #[Groups(['benefit_assignment:read'])]
    #[ORM\Column(name: 'sent_on', type: 'datetime')]
    protected \DateTime $sentOn;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBenefitAssignment(): BenefitAssignment
    {
        return $this->benefitAssignment;
    }

    public function setBenefitAssignment(BenefitAssignment $benefitAssignment): self
    {
        $this->benefitAssignment = $benefitAssignment;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

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

    public function getSentOn(): \DateTime
    {
        return $this->sentOn;
    }

    public function setSentOn(\DateTime $sentOn): self
    {
        $this->sentOn = $sentOn;

        return $this;
    }
}