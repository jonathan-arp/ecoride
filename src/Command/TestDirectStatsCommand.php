<?php

namespace App\Command;

use App\Service\DirectStatsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-direct-stats',
    description: 'Test le service DirectStatsService sans conflit de transaction'
)]
class TestDirectStatsCommand extends Command
{
    public function __construct(
        private DirectStatsService $directStatsService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->note('Test du service DirectStatsService...');
            
            $result = $this->directStatsService->updateTodayStats();
            
            $io->success('Mise à jour réussie avec DirectStatsService !');
            $io->table(
                ['Métrique', 'Valeur'],
                [
                    ['Date', $result['date']],
                    ['Covoiturages', $result['carshares']],
                    ['Crédits plateforme', $result['credits']],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur avec DirectStatsService : ' . $e->getMessage());
            $io->text('Stack trace : ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
