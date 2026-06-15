<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[UniqueEntity(fields: ['code'], message: 'Ce code de projet est déjà utilisé.')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 120)]
    private ?string $name = null;

    /** Préfixe court utilisé pour référencer les tickets (ex. « HUT » → HUT-12). */
    #[ORM\Column(length: 10, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 10)]
    #[Assert\Regex(pattern: '/^[A-Z][A-Z0-9]+$/', message: 'Le code doit être en majuscules (lettres et chiffres), ex. « HUT ».')]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, BoardColumn> */
    #[ORM\OneToMany(targetEntity: BoardColumn::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $columns;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->columns = new ArrayCollection();
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = mb_strtoupper(trim($code));

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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, BoardColumn>
     */
    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public function addColumn(BoardColumn $column): static
    {
        if (!$this->columns->contains($column)) {
            $this->columns->add($column);
            $column->setProject($this);
        }

        return $this;
    }

    public function removeColumn(BoardColumn $column): static
    {
        if ($this->columns->removeElement($column)) {
            if ($column->getProject() === $this) {
                $column->setProject(null);
            }
        }

        return $this;
    }

    /** Position suivante disponible pour une nouvelle colonne. */
    public function getNextColumnPosition(): int
    {
        $max = -1;
        foreach ($this->columns as $column) {
            $max = max($max, $column->getPosition());
        }

        return $max + 1;
    }

    public function getTicketCount(): int
    {
        $count = 0;
        foreach ($this->columns as $column) {
            $count += $column->getTickets()->count();
        }

        return $count;
    }
}
