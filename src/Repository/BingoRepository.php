<?php

namespace App\Repository;

use App\Entity\Bingo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

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
    public function findActiveForOwner(UserInterface $owner): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.deletedAt IS NULL')
            ->andWhere('b.owner = :user')
            ->setParameter('user', $owner)
            ->orderBy('b.year', 'DESC')
            ->addOrderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Bingo[] */
    public function findTrashed(UserInterface $owner): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.deletedAt IS NOT NULL')
            ->andWhere('b.owner = :user')
            ->setParameter('user', $owner)
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

    public function countTrashed(UserInterface $owner): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.deletedAt IS NOT NULL')
            ->andWhere('b.owner = :user')
            ->setParameter('user', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
