<?php

namespace App\Repository;

use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * Find published reviews for a driver
     */
    public function findPublishedByDriver(User $driver): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.driver = :driver')
            ->andWhere('r.status = :status')
            ->setParameter('driver', $driver)
            ->setParameter('status', 'PUBLIE')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate average rating for a driver (published reviews only)
     */
    public function getAverageRatingForDriver(User $driver): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avgRating')
            ->andWhere('r.driver = :driver')
            ->andWhere('r.status = :status')
            ->setParameter('driver', $driver)
            ->setParameter('status', 'PUBLIE')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float) $result, 1) : null;
    }

    /**
     * Count published reviews for a driver
     */
    public function countPublishedByDriver(User $driver): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.driver = :driver')
            ->andWhere('r.status = :status')
            ->setParameter('driver', $driver)
            ->setParameter('status', 'PUBLIE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find reviews pending moderation
     */
    public function findPendingModeration(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', 'EN_ATTENTE')
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if passenger has already reviewed this carshare
     */
    public function hasPassengerReviewedCarshare(User $passenger, $carshareId): bool
    {
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.passenger = :passenger')
            ->andWhere('r.carshare = :carshare')
            ->setParameter('passenger', $passenger)
            ->setParameter('carshare', $carshareId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
