<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ThemeRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ThemeRequestRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['themeReq:read']],
    denormalizationContext: ['groups' => ['themeReq:write']]
)]
class ThemeRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['themeReq:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['themeReq:read', 'themeReq:write'])]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['themeReq:read', 'themeReq:write'])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'themeRequests')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['themeReq:read', 'themeReq:write'])]
    private ?User $requestedBy = null;

    #[ORM\Column(length: 50)]
    #[Groups(['themeReq:read', 'themeReq:write'])]
    private ?string $status = 'pending'; 

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?User $requestedBy): static
    {
        $this->requestedBy = $requestedBy;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }
}
