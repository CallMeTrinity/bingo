<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    public const array PALETTES = ['lavande', 'ciel', 'sorbet', 'matcha'];
    public const array DENSITIES = ['compact', 'regular', 'comfy'];
    public const string DEFAULT_PALETTE = 'lavande';
    public const string DEFAULT_DENSITY = 'regular';
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

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Bingo>
     */
    #[ORM\OneToMany(targetEntity: Bingo::class, mappedBy: 'owner')]
    private Collection $bingos;

    #[ORM\Column(options: ['default' => '{}'])]
    private array $preferences = [];

    public function __construct()
    {
        $this->bingos = new ArrayCollection();
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
        return (string)$this->email;
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
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array)$this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * @return Collection<int, Bingo>
     */
    public function getBingos(): Collection
    {
        return $this->bingos;
    }

    public function addBingo(Bingo $bingo): static
    {
        if (!$this->bingos->contains($bingo)) {
            $this->bingos->add($bingo);
            $bingo->setOwner($this);
        }

        return $this;
    }

    public function removeBingo(Bingo $bingo): static
    {
        if ($this->bingos->removeElement($bingo)) {
            // set the owning side to null (unless already changed)
            if ($bingo->getOwner() === $this) {
                $bingo->setOwner(null);
            }
        }

        return $this;
    }

    public function getPreferences(): array
    {
        return $this->preferences;
    }

    public function setPreferences(array $preferences): static
    {
        $this->preferences = $preferences;

        return $this;
    }

    public function getPalette(): string
    {
        return $this->preferences['palette'] ?? self::DEFAULT_PALETTE;
    }

    public function setPalette(string $palette): static
    {
        if (!in_array($palette, self::PALETTES, true)) {
            throw new InvalidArgumentException('Invalid palette');
        }
        $this->preferences['palette'] = $palette;
        return $this;
    }

    public function getDensity(): string
    {
        return $this->preferences['density'] ?? self::DEFAULT_DENSITY;
    }

    public function setDensity(string $density): static
    {
        if (!in_array($density, self::DENSITIES, true)) {
            throw new InvalidArgumentException('Invalid density');
        }
        $this->preferences['density'] = $density;
        return $this;
    }
}
