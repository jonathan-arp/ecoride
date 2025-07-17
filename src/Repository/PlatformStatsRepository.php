<?php

namespace App\Repository;

use App\Entity\PlatformStats;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlatformStats>
 */
class PlatformStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlatformStats::class);
    }

    /**
     * Récupère les statistiques des 30 derniers jours
     */
    public function getLastThirtyDaysStats(): array
    {
        $thirtyDaysAgo = new \DateTime('-30 days');
        
        return $this->createQueryBuilder('ps')
            ->where('ps.date >= :date')
            ->setParameter('date', $thirtyDaysAgo)
            ->orderBy('ps.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère le total des crédits gagnés
     */
    public function getTotalCreditsEarned(): float
    {
        $result = $this->createQueryBuilder('ps')
            ->select('SUM(ps.creditsEarned) as total')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Trouve ou crée une statistique pour une date donnée
     */
    public function findOrCreateForDate(\DateTimeInterface $date): PlatformStats
    {
        // Rechercher d'abord une statistique existante
        $stat = $this->createQueryBuilder('ps')
            ->where('ps.date = :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$stat) {
            $stat = new PlatformStats();
            $stat->setDate($date);
            $stat->setCarsharesCount(0);
            $stat->setCreditsEarned('0.00');
            // Pas de persist ici, sera fait dans le service
        }
        
        return $stat;
    }
}
