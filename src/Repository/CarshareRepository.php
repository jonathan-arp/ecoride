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
     * Search carshares by departure location, arrival location, exact date and available places
     */
    public function searchCarshares(string $departureLocation, string $arrivalLocation, \DateTimeInterface $date, int $passengers): array
    {
        // Search for the exact date only (not ±3 days to allow alternative dates feature to work)
        $startOfDay = new \DateTime($date->format('Y-m-d') . ' 00:00:00');
        $endOfDay = new \DateTime($date->format('Y-m-d') . ' 23:59:59');

        return $this->createQueryBuilder('c')
            // Search in location fields and detail fields
            ->andWhere('(LOWER(c.start_location) LIKE LOWER(:departure) OR 
                        LOWER(c.start_detail) LIKE LOWER(:departure))')
            ->andWhere('(LOWER(c.end_location) LIKE LOWER(:arrival) OR 
                        LOWER(c.end_detail) LIKE LOWER(:arrival))')
            ->andWhere('c.start BETWEEN :startOfDay AND :endOfDay')
            ->andWhere('c.place >= :passengers')
            ->setParameter('departure', '%' . $departureLocation . '%')
            ->setParameter('arrival', '%' . $arrivalLocation . '%')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->setParameter('passengers', $passengers)
            ->orderBy('c.start', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Find alternative dates for the same route when no results found
     */
    public function findAlternativeDatesForRoute(string $departureLocation, string $arrivalLocation, int $passengers, \DateTimeInterface $originalDate): array
    {
        $today = new \DateTime();
        $originalDateStr = $originalDate->format('Y-m-d');
        
        return $this->createQueryBuilder('c')
            // Search in location fields and detail fields
            ->andWhere('(LOWER(c.start_location) LIKE LOWER(:departure) OR 
                        LOWER(c.start_detail) LIKE LOWER(:departure))')
            ->andWhere('(LOWER(c.end_location) LIKE LOWER(:arrival) OR 
                        LOWER(c.end_detail) LIKE LOWER(:arrival))')
            ->andWhere('c.place >= :passengers')
            ->andWhere('c.start >= :today')
            ->andWhere('c.start < :originalDateStart OR c.start >= :originalDateEnd')
            ->setParameter('departure', '%' . $departureLocation . '%')
            ->setParameter('arrival', '%' . $arrivalLocation . '%')
            ->setParameter('passengers', $passengers)
            ->setParameter('today', $today)
            ->setParameter('originalDateStart', new \DateTime($originalDateStr . ' 00:00:00'))
            ->setParameter('originalDateEnd', new \DateTime($originalDateStr . ' 23:59:59'))
            ->orderBy('c.start', 'ASC')
            ->setMaxResults(10)
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
