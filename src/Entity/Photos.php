<?php

namespace App\Entity;

use App\Repository\PhotosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: PhotosRepository::class)]
class Photos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $photo_url = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    // Nullable pour pouvoir stocker une date de prise extraite du EXIF si dispo
    #[ORM\Column(type:"datetime_immutable", nullable:true)]
    private ?\DateTimeImmutable $date_prise = null;

    #[ORM\Column]
    private ?bool $public = null;

    /**
     * @var Collection<int, Album>
     */
    #[ORM\ManyToMany(targetEntity: Album::class, mappedBy: 'photos')]
    private Collection $albums;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $localisation = null;

    /**
     * @var Collection<int, Themes>
     */
    #[ORM\ManyToMany(targetEntity: Themes::class, inversedBy: 'photos')]
    private Collection $themes;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_added = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
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