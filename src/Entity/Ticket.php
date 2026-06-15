<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Priority;
use App\Repository\TicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 200)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(enumType: Priority::class)]
    private Priority $priority = Priority::Medium;

    /** Position du ticket dans sa colonne (0 = en haut). */
    #[ORM\Column]
    private int $position = 0;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?BoardColumn $column = null;

    /** Personne à qui le ticket est assigné (optionnel). */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignee = null;

    /** Auteur du ticket. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $reporter = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

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

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriority(): Priority
    {
        return $this->priority;
    }

    public function setPriority(Priority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getColumn(): ?BoardColumn
    {
        return $this->column;
    }

    public function setColumn(?BoardColumn $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): static
    {
        $this->assignee = $assignee;

        return $this;
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(?User $reporter): static
    {
        $this->reporter = $reporter;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** Référence lisible du ticket, ex. « HUT-12 ». */
    public function getReference(): string
    {
        $code = $this->column?->getProject()?->getCode() ?? 'TCK';

        return \sprintf('%s-%d', $code, (int) $this->id);
    }

    public function getProject(): ?Project
    {
        return $this->column?->getProject();
    }
}
