<?php

namespace App\Service;

use App\Entity\PlatformStats;
use App\Repository\PlatformStatsRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class DirectStatsService
{
    public function __construct(
        private Connection $connection,
        private EntityManagerInterface $entityManager,
        private PlatformStatsRepository $platformStatsRepository
    ) {}

    /**
     * Met à jour les statistiques en utilisant du SQL direct pour éviter les conflits de transaction
     */
    public function updateTodayStats(): array
    {
        $date = new \DateTime('today');
        $startOfDay = $date->format('Y-m-d') . ' 00:00:00';
        $endOfDay = $date->format('Y-m-d') . ' 23:59:59';

        // Compter les covoiturages avec du SQL direct
        $carsharesCount = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM carshare WHERE start >= ? AND start <= ?',
            [$startOfDay, $endOfDay]
        );

        // Compter les crédits de la plateforme avec du SQL direct
        $creditsEarned = $this->connection->fetchOne(
            'SELECT COALESCE(SUM(amount), 0) FROM credit 
             WHERE created_at >= ? AND created_at <= ? 
             AND type = ? AND amount > 0',
            [$startOfDay, $endOfDay, 'PLATFORM_FEE']
        );

        // Insérer ou mettre à jour avec du SQL direct
        $dateStr = $date->format('Y-m-d');
        
        // Vérifier si une entrée existe déjà
        $existingId = $this->connection->fetchOne(
            'SELECT id FROM platform_stats WHERE date = ?',
            [$dateStr]
        );

        if ($existingId) {
            // Mettre à jour
            $this->connection->executeStatement(
                'UPDATE platform_stats SET carshares_count = ?, credits_earned = ? WHERE id = ?',
                [(int) $carsharesCount, (string) $creditsEarned, $existingId]
            );
        } else {
            // Insérer
            $this->connection->executeStatement(
                'INSERT INTO platform_stats (date, carshares_count, credits_earned) VALUES (?, ?, ?)',
                [$dateStr, (int) $carsharesCount, (string) $creditsEarned]
            );
        }

        return [
            'date' => $dateStr,
            'carshares' => (int) $carsharesCount,
            'credits' => (string) $creditsEarned
        ];
    }
}
