<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Photos;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    /** @var list<string> Rôles de l'utilisateur — non exposés dans l'API pour l'instant */
    #[ORM\Column]
    private array $roles = [];

    /** @var string Mot de passe hashé */
    #[ORM\Column]
    #[Groups(['user:write'])]
    private ?string $password = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private bool $isVerified = false;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $lastName = null;

    /** @var Collection<int, Photos> Non exposé pour l'instant */
    #[ORM\OneToMany(targetEntity: Photos::class, mappedBy: 'userPhoto')]
    private Collection $photos;

    /** @var Collection<int, ThemeRequest> Non exposé pour l'instant */
    #[ORM\OneToMany(mappedBy: 'requestedBy', targetEntity: ThemeRequest::class)]
    private Collection $themeRequests;

    /** @var Collection<int, Notification> Non exposé pour l'instant */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'recipient')]
    private Collection $notifications;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->themeRequests = new ArrayCollection();
        $this->notifications = new ArrayCollection();
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

    /** @see UserInterface */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /** @see UserInterface */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Chaque utilisateur a au moins ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /** @see PasswordAuthenticatedUserInterface */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /** Sérialisation personnalisée pour éviter de stocker le mot de passe en clair dans la session. */
    public function __serialize(): array
    {
        return [
            'id'         => $this->id,
            'email'      => $this->email,
            'roles'      => $this->roles,
            'password'   => $this->password,
            'isVerified' => $this->isVerified,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id         = $data['id'];
        $this->email      = $data['email'];
        $this->roles      = $data['roles'];
        $this->password   = $data['password'];
        $this->isVerified = $data['isVerified'];
    }

    public function eraseCredentials(): void
    {
        // Efface les données sensibles temporaires (ex : plainPassword)
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

    /** @return Collection<int, Photos> */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photos $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setUserPhoto($this);
        }

        return $this;
    }

    public function removePhoto(Photos $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            if ($photo->getUserPhoto() === $this) {
                $photo->setUserPhoto(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, ThemeRequest> */
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
            if ($themeRequest->getRequestedBy() === $this) {
                $themeRequest->setRequestedBy(null);
            }
        }

        return $this;
    }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): static { $this->lastName = $lastName; return $this; }

    public function __toString(): string
    {
        return (string) $this->email;
    }

    /** @return Collection<int, Notification> */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setRecipient($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getRecipient() === $this) {
                $notification->setRecipient(null);
            }
        }

        return $this;
    }
}
