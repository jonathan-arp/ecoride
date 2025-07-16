<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\Connection;

#[AsCommand(
    name: 'app:session:status',
    description: 'Affiche le statut des sessions stockées en base de données',
)]
class SessionStatusCommand extends Command
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
            // Vérifier l'existence de la table sessions
            $tableExists = $this->connection->fetchOne(
                "SELECT COUNT(*) FROM information_schema.tables 
                 WHERE table_schema = DATABASE() AND table_name = 'sessions'"
            );

            if (!$tableExists) {
                $io->error('La table sessions n\'existe pas. Exécutez la migration d\'abord.');
                return Command::FAILURE;
            }

            $io->success('✓ Table sessions trouvée en base de données');

            // Compter les sessions actives
            $activeSessions = $this->connection->fetchOne('SELECT COUNT(*) FROM sessions');
            $io->info(sprintf('Sessions actives: %d', $activeSessions));

            // Compter les sessions expirées
            $expiredSessions = $this->connection->fetchOne(
                'SELECT COUNT(*) FROM sessions WHERE sess_time + sess_lifetime < ?',
                [time()]
            );
            $io->info(sprintf('Sessions expirées: %d', $expiredSessions));

            // Afficher quelques sessions récentes
            if ($activeSessions > 0) {
                $recentSessions = $this->connection->fetchAllAssociative(
                    'SELECT sess_id, sess_time, sess_lifetime, 
                     FROM_UNIXTIME(sess_time) as created_at,
                     FROM_UNIXTIME(sess_time + sess_lifetime) as expires_at
                     FROM sessions 
                     ORDER BY sess_time DESC 
                     LIMIT 5'
                );

                $io->section('Sessions récentes:');
                foreach ($recentSessions as $session) {
                    $io->text(sprintf(
                        'ID: %s | Créée: %s | Expire: %s',
                        substr($session['sess_id'], 0, 12) . '...',
                        $session['created_at'],
                        $session['expires_at']
                    ));
                }
            }

            $io->success('Configuration des sessions en BDD opérationnelle !');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la vérification : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
