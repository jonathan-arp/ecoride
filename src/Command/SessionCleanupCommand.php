<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\Connection;

#[AsCommand(
    name: 'app:session:cleanup',
    description: 'Nettoie les sessions expirées de la base de données',
)]
class SessionCleanupCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Supprimer les sessions expirées
            $deletedCount = $this->connection->executeStatement(
                'DELETE FROM sessions WHERE sess_time + sess_lifetime < ?',
                [time()]
            );

            $io->success(sprintf('Sessions nettoyées avec succès. %d sessions expirées supprimées.', $deletedCount));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors du nettoyage des sessions : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
