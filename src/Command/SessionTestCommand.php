<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Doctrine\DBAL\Connection;

#[AsCommand(
    name: 'app:session:test',
    description: 'Teste le fonctionnement des sessions en base de données',
)]
class SessionTestCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private PdoSessionHandler $sessionHandler
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->title('Test des sessions en base de données');

            // Compter les sessions avant
            $sessionsBefore = $this->connection->fetchOne('SELECT COUNT(*) FROM sessions');
            $io->info(sprintf('Sessions en base avant test: %d', $sessionsBefore));

            // Créer une session de test
            $storage = new NativeSessionStorage(
                [
                    'cookie_lifetime' => 3600,
                    'gc_maxlifetime' => 3600,
                ],
                $this->sessionHandler
            );

            $session = new Session($storage);
            $session->start();
            $session->set('test_key', 'Test session data - ' . date('Y-m-d H:i:s'));
            $sessionId = $session->getId();
            
            $io->success(sprintf('Session créée avec ID: %s', $sessionId));
            $io->text(sprintf('Données de test stockées: %s', $session->get('test_key')));

            // Forcer la sauvegarde
            $session->save();

            // Vérifier en base
            $sessionData = $this->connection->fetchAssociative(
                'SELECT sess_id, sess_time, sess_lifetime FROM sessions WHERE sess_id = ?',
                [$sessionId]
            );

            if ($sessionData) {
                $io->success('✓ Session trouvée en base de données !');
                $io->text(sprintf('ID: %s', $sessionData['sess_id']));
                $io->text(sprintf('Heure création: %s', date('Y-m-d H:i:s', $sessionData['sess_time'])));
                $io->text(sprintf('Durée de vie: %d secondes', $sessionData['sess_lifetime']));
            } else {
                $io->error('✗ Session non trouvée en base de données');
                return Command::FAILURE;
            }

            // Compter les sessions après
            $sessionsAfter = $this->connection->fetchOne('SELECT COUNT(*) FROM sessions');
            $io->info(sprintf('Sessions en base après test: %d', $sessionsAfter));

            // Nettoyer la session de test
            $this->sessionHandler->destroy($sessionId);
            $io->info('Session de test supprimée');

            $io->success('Test des sessions en BDD réussi ! 🎉');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors du test: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
