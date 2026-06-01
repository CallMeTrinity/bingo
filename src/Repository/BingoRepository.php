<?php

namespace App\Repository;

use App\Entity\Bingo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bingo>
 */
class BingoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bingo::class);
    }

    /** @return Bingo[] */
    public function findActive(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.deletedAt IS NULL')
            ->orderBy('b.year', 'DESC')
            ->addOrderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Bingo[] */
    public function findTrashed(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.deletedAt IS NOT NULL')
            ->orderBy('b.deletedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneActiveBySlug(string $slug): ?Bingo
    {
        return $this->findOneBy(['slug' => $slug, 'deletedAt' => null]);
    }

    public function findOneTrashedBySlug(string $slug): ?Bingo
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.slug = :slug')
            ->andWhere('b.deletedAt IS NOT NULL')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countTrashed(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.deletedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
