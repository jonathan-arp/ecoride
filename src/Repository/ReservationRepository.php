<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Find reservations by passenger
     */
    public function findByPassenger($passenger): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.passenger = :passenger')
            ->setParameter('passenger', $passenger)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reservations by carshare
     */
    public function findByCarshare($carshare): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.carshare = :carshare')
            ->setParameter('carshare', $carshare)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
