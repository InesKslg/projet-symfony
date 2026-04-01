<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PhotosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PhotosRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['photo:read']],
    denormalizationContext: ['groups' => ['photo:write']]
)]
class Photos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['photo:read', 'album:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['photo:read', 'photo:write', 'album:read'])]
    private ?string $photo_url = null;

    #[ORM\Column(length: 255)]
    #[Groups(['photo:read', 'photo:write'])]
    private ?string $description = null;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    #[Groups(['photo:read', 'photo:write'])]
    private ?\DateTimeImmutable $date_prise = null;

    #[ORM\Column]
    #[Groups(['photo:read', 'photo:write'])]
    private ?bool $public = null;

    /**
     * @var Collection<int, Album>
     */
    #[ORM\ManyToMany(targetEntity: Album::class, mappedBy: 'photos')]
    #[Groups(['photo:read', 'photo:write'])]
    private Collection $albums;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['photo:read', 'photo:write'])]
    private ?string $localisation = null;

    /**
     * @var Collection<int, Themes>
     */
    #[ORM\ManyToMany(targetEntity: Themes::class, inversedBy: 'photos')]
    #[ORM\JoinTable(name: 'photos_themes')]
    #[Groups(['photo:read', 'photo:write'])]
    private Collection $themes;

    #[ORM\Column]
    #[Groups(['photo:read'])]
    private ?\DateTimeImmutable $date_added = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[Groups(['photo:read', 'photo:write'])]
    private ?User $userPhoto = null;

    public function __construct()
    {
        $this->albums = new ArrayCollection();
        $this->themes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photo_url;
    }

    public function setPhotoUrl(string $photo_url): static
    {
        $this->photo_url = $photo_url;
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

    public function getDateAdded(): ?\DateTimeImmutable
    {
        return $this->date_added;
    }

    public function setDateAdded(\DateTimeImmutable $date_added): static
    {
        $this->date_added = $date_added;
        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;
        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): static
    {
        $this->localisation = $localisation;
        return $this;
    }

    public function getDatePrise(): ?\DateTimeImmutable
    {
        return $this->date_prise;
    }

    public function setDatePrise(?\DateTimeImmutable $date_prise): static
    {
        $this->date_prise = $date_prise;
        return $this;
    }

    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): static
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->addPhoto($this);
        }
        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
            $album->removePhoto($this);
        }
        return $this;
    }

    public function getThemes(): Collection
    {
        return $this->themes;
    }

    public function addTheme(Themes $theme): static
    {
        if (!$this->themes->contains($theme)) {
            $this->themes->add($theme);
        }
        return $this;
    }

    public function removeTheme(Themes $theme): static
    {
        $this->themes->removeElement($theme);
        return $this;
    }

    public function getUserPhoto(): ?User
    {
        return $this->userPhoto;
    }

    public function setUserPhoto(?User $userPhoto): static
    {
        $this->userPhoto = $userPhoto;
        return $this;
    }
}
