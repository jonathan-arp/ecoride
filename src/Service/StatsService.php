<?php

namespace App\Service;

use App\Entity\Carshare;
use App\Entity\Credit;
use App\Entity\PlatformStats;
use App\Repository\CarshareRepository;
use App\Repository\CreditRepository;
use App\Repository\PlatformStatsRepository;
use Doctrine\ORM\EntityManagerInterface;

class StatsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CarshareRepository $carshareRepository,
        private CreditRepository $creditRepository,
        private PlatformStatsRepository $platformStatsRepository
    ) {}

    /**
     * Met à jour les statistiques pour une date donnée
     */
    public function updateStatsForDate(\DateTimeInterface $date): PlatformStats
    {
        $stat = $this->platformStatsRepository->findOrCreateForDate($date);
        
        // Définir les bornes de la journée
        $startOfDay = new \DateTime($date->format('Y-m-d') . ' 00:00:00');
        $endOfDay = new \DateTime($date->format('Y-m-d') . ' 23:59:59');
        
        // Compter les covoiturages créés ce jour (utilisons la date de départ)
        $carsharesCount = $this->carshareRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.start >= :startOfDay')
            ->andWhere('c.start <= :endOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Calculer les crédits gagnés par la plateforme ce jour
        // La plateforme gagne des frais sur chaque transaction
        $creditsEarned = $this->creditRepository->createQueryBuilder('cr')
            ->select('SUM(cr.amount)')
            ->where('cr.createdAt >= :startOfDay')
            ->andWhere('cr.createdAt <= :endOfDay')
            ->andWhere('cr.type = :type')
            ->andWhere('cr.amount > 0') // Seulement les crédits positifs
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->setParameter('type', 'PLATFORM_FEE')
            ->getQuery()
            ->getSingleScalarResult();
        
        $stat->setCarsharesCount((int) $carsharesCount);
        $stat->setCreditsEarned((string) ($creditsEarned ?? 0));
        
        // Si une transaction est active, on ne fait qu'un persist
        // Le flush sera géré par la transaction parente
        $this->entityManager->persist($stat);
        
        // On ne fait un flush que si aucune transaction n'est active
        if (!$this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->flush();
        }
        
        return $stat;
    }

    /**
     * Met à jour les statistiques pour aujourd'hui
     */
    public function updateTodayStats(): PlatformStats
    {
        return $this->updateStatsForDate(new \DateTime('today'));
    }

    /**
     * Récupère les données pour le graphique des covoiturages
     */
    public function getCarsharesChartData(int $days = 30): array
    {
        $stats = $this->platformStatsRepository->getLastThirtyDaysStats();
        
        $data = [
            'labels' => [],
            'data' => []
        ];
        
        foreach ($stats as $stat) {
            $data['labels'][] = $stat->getDate()->format('d/m');
            $data['data'][] = $stat->getCarsharesCount();
        }
        
        return $data;
    }

    /**
     * Récupère les données pour le graphique des crédits
     */
    public function getCreditsChartData(int $days = 30): array
    {
        $stats = $this->platformStatsRepository->getLastThirtyDaysStats();
        
        $data = [
            'labels' => [],
            'data' => []
        ];
        
        foreach ($stats as $stat) {
            $data['labels'][] = $stat->getDate()->format('d/m');
            $data['data'][] = (float) $stat->getCreditsEarned();
        }
        
        return $data;
    }

    /**
     * Récupère le total des crédits gagnés par la plateforme
     */
    public function getTotalPlatformCredits(): float
    {
        return $this->platformStatsRepository->getTotalCreditsEarned();
    }

    /**
     * Récupère les statistiques générales
     */
    public function getGeneralStats(): array
    {
        $totalCarshares = $this->carshareRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalUsers = $this->entityManager->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from('App\Entity\User', 'u')
            ->where('u.roles NOT LIKE :admin')
            ->andWhere('u.roles NOT LIKE :employee')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('employee', '%ROLE_EMPLOYEE%')
            ->getQuery()
            ->getSingleScalarResult();

        $totalCreditsInCirculation = $this->creditRepository->createQueryBuilder('cr')
            ->select('SUM(cr.amount)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalReviews = $this->entityManager->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from('App\Entity\Review', 'r')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'totalCarshares' => (int) $totalCarshares,
            'totalUsers' => (int) $totalUsers,
            'totalPlatformCredits' => $this->getTotalPlatformCredits(),
            'totalCreditsInCirculation' => (float) ($totalCreditsInCirculation ?? 0),
            'totalReviews' => (int) $totalReviews
        ];
    }
}
