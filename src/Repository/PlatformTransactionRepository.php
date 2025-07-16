<?php

namespace App\Repository;

use App\Entity\PlatformTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlatformTransaction>
 */
class PlatformTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlatformTransaction::class);
    }

    /**
     * Find pending transaction for a reservation
     */
    public function findPendingByReservation($reservation): ?PlatformTransaction
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.reservation = :reservation')
            ->andWhere('pt.status = :status')
            ->setParameter('reservation', $reservation)
            ->setParameter('status', 'PENDING')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all pending transactions
     */
    public function findAllPending(): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.status = :status')
            ->setParameter('status', 'PENDING')
            ->orderBy('pt.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find transactions by user (from or to)
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.fromUser = :user OR pt.toUser = :user')
            ->setParameter('user', $user)
            ->orderBy('pt.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
