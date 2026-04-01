<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['notif:read']],
    denormalizationContext: ['groups' => ['notif:write']]
)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['notif:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[Groups(['notif:read', 'notif:write'])]
    private ?User $recipient = null;

    #[ORM\Column(length: 255)]
    #[Groups(['notif:read', 'notif:write'])]
    private ?string $message = null;

    #[ORM\Column]
    #[Groups(['notif:read', 'notif:write'])]
    private ?bool $isRead = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): static
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function isRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        return $this;
    }
}
