<?php

namespace App\Command;

use App\Entity\Carshare;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:carshare:check-expired',
    description: 'Vérifie et marque les carshares expirés',
)]
class CheckExpiredCarsharesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification des carshares expirés');

        try {
            $carshareRepository = $this->entityManager->getRepository(Carshare::class);
            
            // Récupérer tous les carshares actifs
            $activeCarshares = $carshareRepository->createQueryBuilder('c')
                ->where('c.status != :expired')
                ->setParameter('expired', 'EXPIRED')
                ->getQuery()
                ->getResult();

            $expiredCount = 0;
            
            foreach ($activeCarshares as $carshare) {
                if ($carshare->isExpired() && $carshare->getStatus() !== 'EXPIRED') {
                    $carshare->markAsExpired();
                    $this->entityManager->persist($carshare);
                    $expiredCount++;
                    
                    $io->text(sprintf(
                        'Carshare ID %d marqué comme expiré (date: %s)',
                        $carshare->getId(),
                        $carshare->getDate()->format('Y-m-d H:i')
                    ));
                }
            }

            if ($expiredCount > 0) {
                $this->entityManager->flush();
                $io->success(sprintf('%d carshare(s) marqué(s) comme expiré(s).', $expiredCount));
            } else {
                $io->info('Aucun carshare expiré trouvé.');
            }

            // Statistiques
            $totalActive = count($activeCarshares) - $expiredCount;
            $totalExpired = $carshareRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.status = :expired')
                ->setParameter('expired', 'EXPIRED')
                ->getQuery()
                ->getSingleScalarResult();

            $io->section('Statistiques:');
            $io->definitionList(
                ['Carshares actifs' => $totalActive],
                ['Carshares expirés (total)' => $totalExpired],
                ['Nouveaux expirés (cette exécution)' => $expiredCount]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la vérification: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
