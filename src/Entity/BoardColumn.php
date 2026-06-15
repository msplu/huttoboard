<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BoardColumnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Colonne du tableau kanban (ex. « À faire », « En cours », « Terminé »).
 * Nommée BoardColumn car « Column » est un mot réservé SQL.
 */
#[ORM\Entity(repositoryClass: BoardColumnRepository::class)]
class BoardColumn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 80)]
    private ?string $name = null;

    /** Position de la colonne dans le tableau (0 = la plus à gauche). */
    #[ORM\Column]
    private int $position = 0;

    /** Couleur d'accent de l'en-tête de colonne. */
    #[ORM\Column(length: 7)]
    #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/', message: 'Couleur hexadécimale invalide.')]
    private string $color = '#64748b';

    /** Limite optionnelle de travail en cours (WIP). null = illimité. */
    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $wipLimit = null;

    #[ORM\ManyToOne(inversedBy: 'columns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    /** @var Collection<int, Ticket> */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'column', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $tickets;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getWipLimit(): ?int
    {
        return $this->wipLimit;
    }

    public function setWipLimit(?int $wipLimit): static
    {
        $this->wipLimit = $wipLimit;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setColumn($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            if ($ticket->getColumn() === $this) {
                $ticket->setColumn(null);
            }
        }

        return $this;
    }

    public function getNextTicketPosition(): int
    {
        $max = -1;
        foreach ($this->tickets as $ticket) {
            $max = max($max, $ticket->getPosition());
        }

        return $max + 1;
    }

    public function isOverWipLimit(): bool
    {
        return null !== $this->wipLimit && $this->tickets->count() > $this->wipLimit;
    }
}
