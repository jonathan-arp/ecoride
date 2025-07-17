<?php

namespace App\Command;

use App\Service\StatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug-transactions',
    description: 'Debug des transactions Doctrine'
)]
class DebugTransactionsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StatsService $statsService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->note('État initial des transactions...');
            $io->text('Transaction active: ' . ($this->entityManager->getConnection()->isTransactionActive() ? 'OUI' : 'NON'));
            
            $io->note('Test de mise à jour des statistiques...');
            $stat = $this->statsService->updateTodayStats();
            
            $io->text('Transaction active après mise à jour: ' . ($this->entityManager->getConnection()->isTransactionActive() ? 'OUI' : 'NON'));
            
            $io->success('Test terminé avec succès !');
            $io->text(sprintf('Statistiques : %d covoiturages, %s crédits', 
                $stat->getCarsharesCount(), 
                $stat->getCreditsEarned()
            ));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur détectée : ' . $e->getMessage());
            $io->text('Transaction active lors de l\'erreur: ' . ($this->entityManager->getConnection()->isTransactionActive() ? 'OUI' : 'NON'));
            $io->text('Stack trace : ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
