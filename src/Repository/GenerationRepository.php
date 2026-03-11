<?php

namespace App\Repository;

use App\Entity\Generation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Generation>
 */
class GenerationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Generation::class);
    }

    public function countThisMonth($user): int
    {
        $from = new \DateTime('first day of this month 00:00:00');
        $to   = new \DateTime('last day of this month 23:59:59');

        return $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.user = :user')
            ->andWhere('g.createdAt BETWEEN :from AND :to')
            ->setParameter('user', $user)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
