<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ThemesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ThemesRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['theme:read']],
    denormalizationContext: ['groups' => ['theme:write']]
)]
class Themes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['theme:read', 'photo:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['theme:read', 'theme:write', 'photo:read'])]
    private ?string $Nom = null;

    /**
     * @var Collection<int, Photos>
     */
    #[ORM\ManyToMany(targetEntity: Photos::class, mappedBy: 'themes')]
    #[Groups(['theme:read'])]
    private Collection $photos;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->Nom;
    }

    public function setNom(string $Nom): static
    {
        $this->Nom = $Nom;
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
}
