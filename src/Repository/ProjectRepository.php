<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function save(Project $project, bool $flush = true): void
    {
        $this->getEntityManager()->persist($project);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Project $project, bool $flush = true): void
    {
        $this->getEntityManager()->remove($project);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Project[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
