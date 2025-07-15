<?php

namespace App\Repository;

use App\Entity\Carshare;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Carshare>
 */
class CarshareRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Carshare::class);
    }

    //    /**
    //     * @return Carshare[] Returns an array of Carshare objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * Search carshares by departure location, arrival location, date and available places
     */
    public function searchCarshares(string $departureLocation, string $arrivalLocation, \DateTimeInterface $date, int $passengers): array
    {
        // Calculate date range (3 days before and after the requested date)
        $startDate = new \DateTime($date->format('Y-m-d'));
        $startDate->modify('-3 days');
        
        $endDate = new \DateTime($date->format('Y-m-d'));
        $endDate->modify('+3 days');

        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.start_location) LIKE LOWER(:departure)')
            ->andWhere('LOWER(c.end_location) LIKE LOWER(:arrival)')
            ->andWhere('c.start BETWEEN :startDate AND :endDate')
            ->andWhere('c.place >= :passengers')
            ->setParameter('departure', '%' . $departureLocation . '%')
            ->setParameter('arrival', '%' . $arrivalLocation . '%')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('passengers', $passengers)
            ->orderBy('c.start', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    //    public function findOneBySomeField($value): ?Carshare
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
