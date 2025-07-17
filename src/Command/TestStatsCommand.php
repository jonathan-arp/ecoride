<?php

namespace App\Command;

use App\Service\StatsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-stats',
    description: 'Test la mise à jour des statistiques'
)]
class TestStatsCommand extends Command
{
    public function __construct(
        private StatsService $statsService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->note('Test de la mise à jour des statistiques...');
            
            // Test mise à jour des stats d'aujourd'hui
            $stat = $this->statsService->updateTodayStats();
            $io->success(sprintf('Statistiques d\'aujourd\'hui mises à jour : %d covoiturages, %s crédits', 
                $stat->getCarsharesCount(), 
                $stat->getCreditsEarned()
            ));

            // Test récupération des stats générales
            $generalStats = $this->statsService->getGeneralStats();
            $io->table(
                ['Métrique', 'Valeur'],
                [
                    ['Covoiturages totaux', $generalStats['totalCarshares']],
                    ['Utilisateurs totaux', $generalStats['totalUsers']],
                    ['Avis totaux', $generalStats['totalReviews']],
                    ['Crédits plateforme', $generalStats['totalPlatformCredits']],
                    ['Crédits en circulation', $generalStats['totalCreditsInCirculation']],
                ]
            );

            $io->success('Tous les tests de statistiques sont passés avec succès !');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors du test des statistiques : ' . $e->getMessage());
            $io->text('Stack trace : ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
