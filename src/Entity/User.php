<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Photos;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    /**
     * @var Collection<int, photos>
     */
    #[ORM\OneToMany(targetEntity: Photos::class, mappedBy: 'userPhoto')]
    private Collection $photos;

    /**
     * @var Collection<int, ThemeRequest>
     */
    #[ORM\OneToMany(targetEntity: ThemeRequest::class, mappedBy: 'RequestedBy')]
    private Collection $themeRequests;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->themeRequests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Sérialisation personnalisée pour éviter de stocker le mot de passe en clair dans la session.
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'roles' => $this->roles,
            'password' => $this->password,
            'isVerified' => $this->isVerified,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->roles = $data['roles'];
        $this->password = $data['password'];
        $this->isVerified = $data['isVerified'];
    }

    public function eraseCredentials(): void
    {
        // Nettoie les données sensibles temporaires si tu en ajoutes (ex: plainPassword)
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    /**
     * @return Collection<int, photos>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(photos $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setUserPhoto($this);
        }

        return $this;
    }

    public function removePhoto(photos $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getUserPhoto() === $this) {
                $photo->setUserPhoto(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ThemeRequest>
     */
    public function getThemeRequests(): Collection
    {
        return $this->themeRequests;
    }

    public function addThemeRequest(ThemeRequest $themeRequest): static
    {
        if (!$this->themeRequests->contains($themeRequest)) {
            $this->themeRequests->add($themeRequest);
            $themeRequest->setRequestedBy($this);
        }

        return $this;
    }

    public function removeThemeRequest(ThemeRequest $themeRequest): static
    {
        if ($this->themeRequests->removeElement($themeRequest)) {
            // set the owning side to null (unless already changed)
            if ($themeRequest->getRequestedBy() === $this) {
                $themeRequest->setRequestedBy(null);
            }
        }

        return $this;
    }
}
