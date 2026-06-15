<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BoardColumn;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BoardColumn>
 */
class BoardColumnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoardColumn::class);
    }

    public function save(BoardColumn $column, bool $flush = true): void
    {
        $this->getEntityManager()->persist($column);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BoardColumn $column, bool $flush = true): void
    {
        $this->getEntityManager()->remove($column);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Colonnes d'un projet, triées par position.
     *
     * @return BoardColumn[]
     */
    public function findByProjectOrdered(Project $project): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.project = :project')
            ->setParameter('project', $project)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
