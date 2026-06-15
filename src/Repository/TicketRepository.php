<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BoardColumn;
use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function save(Ticket $ticket, bool $flush = true): void
    {
        $this->getEntityManager()->persist($ticket);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ticket $ticket, bool $flush = true): void
    {
        $this->getEntityManager()->remove($ticket);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Plus grande position utilisée dans une colonne (-1 si vide).
     */
    public function maxPositionInColumn(BoardColumn $column): int
    {
        $max = $this->createQueryBuilder('t')
            ->select('MAX(t.position)')
            ->andWhere('t.column = :column')
            ->setParameter('column', $column)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $max ? -1 : (int) $max;
    }
}
