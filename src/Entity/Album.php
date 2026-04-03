<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AlbumRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['album:read']],
    denormalizationContext: ['groups' => ['album:write']]
)]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['album:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['album:read', 'album:write'])]
    #[Assert\Length(
        max: 6,
        maxMessage: "Le nom de l'album ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $categorie = null;

    /**
     * @var Collection<int, Photos>
     */
    #[ORM\ManyToMany(targetEntity: Photos::class, inversedBy: 'albums')]
    #[Groups(['album:read', 'album:write'])]
    private Collection $photos;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['album:read', 'album:write'])]
    private ?User $user = null;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    /**
     * @return Collection<int, Photos>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photos $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
        }
        return $this;
    }

    public function removePhoto(Photos $photo): static
    {
        $this->photos->removeElement($photo);
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}