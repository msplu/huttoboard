<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Niveau de priorité d'un ticket.
 */
enum Priority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Basse',
            self::Medium => 'Moyenne',
            self::High => 'Haute',
            self::Urgent => 'Urgente',
        };
    }

    /** Couleur (classe CSS modificatrice) associée à la priorité. */
    public function color(): string
    {
        return match ($this) {
            self::Low => '#16a34a',
            self::Medium => '#2563eb',
            self::High => '#ea580c',
            self::Urgent => '#dc2626',
        };
    }

    /** Poids numérique, utile pour le tri. */
    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Urgent => 4,
        };
    }
}
