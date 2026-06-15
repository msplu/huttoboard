<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cette adresse e-mail.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_USER = 'ROLE_USER';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    /** Mot de passe haché. */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 120)]
    private ?string $fullName = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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
     * Identifiant visuel de l'utilisateur pour le système de sécurité.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Tout utilisateur possède au moins ROLE_USER.
        $roles[] = self::ROLE_USER;

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function isAdmin(): bool
    {
        return \in_array(self::ROLE_ADMIN, $this->roles, true);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    /** Initiales utilisées pour les avatars. */
    public function getInitials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->fullName)) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ('' !== $part) {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
            if (mb_strlen($initials) >= 2) {
                break;
            }
        }

        return '' !== $initials ? $initials : mb_strtoupper(mb_substr((string) $this->email, 0, 1));
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Efface les données sensibles temporaires (aucune ici).
     */
    public function eraseCredentials(): void
    {
    }
}
