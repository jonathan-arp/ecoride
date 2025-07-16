<?php

namespace App\Command;

use App\Entity\Carshare;
use App\Repository\CarshareRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:handle-expired-carshares',
    description: 'Handle carshares that are expired (1 hour after departure without starting) - OPTIONAL: Real-time checks are performed when users browse',
)]
class HandleExpiredCarsharesCommand extends Command
{
    public function __construct(
        private CarshareRepository $carshareRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Find carshares that haven't started yet
        $pendingCarshares = $this->carshareRepository->findBy(['tripStatus' => null]);
        
        $expiredCount = 0;
        
        foreach ($pendingCarshares as $carshare) {
            if ($carshare->isExpired()) {
                $carshare->markAsExpired();
                $this->entityManager->persist($carshare);
                $expiredCount++;
                
                $io->writeln(sprintf(
                    'Marked carshare #%d as EXPIRED (departure: %s)', 
                    $carshare->getId(),
                    $carshare->getStart()->format('Y-m-d H:i')
                ));

                // TODO: Handle reservations for expired carshares
                // - Refund credits to passengers
                // - Send notifications to driver and passengers
                // - Cancel pending transactions
            }
        }

        if ($expiredCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Processed %d expired carshares.', $expiredCount));
        } else {
            $io->info('No expired carshares found.');
        }

        $io->note('Note: Expired carshares are also automatically handled when users browse the site (search, my carshares, my reservations).');

        return Command::SUCCESS;
    }
}
