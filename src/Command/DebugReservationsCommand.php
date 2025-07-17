<?php

namespace App\Command;

use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'debug:reservations',
    description: 'Debug reservations for user dd@dd.fr',
)]
class DebugReservationsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private ReservationRepository $reservationRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = $this->userRepository->findOneBy(['email' => 'dd@dd.fr']);
        if (!$user) {
            $io->error('User dd@dd.fr not found');
            return Command::FAILURE;
        }

        $io->success(sprintf('Found user: %s %s (%s)', $user->getFirstname(), $user->getLastname(), $user->getEmail()));
        $io->info(sprintf('User can be passenger: %s', $user->canBePassenger() ? 'YES' : 'NO'));

        $reservations = $this->reservationRepository->findByPassenger($user);
        $io->info(sprintf('Found %d reservations', count($reservations)));

        foreach ($reservations as $reservation) {
            $io->writeln(sprintf(
                '- Reservation #%d: %s -> %s (%.2f credits, %d passengers, status: %s)',
                $reservation->getId(),
                $reservation->getCarshare()->getStartLocation(),
                $reservation->getCarshare()->getEndLocation(),
                $reservation->getPrice(),
                $reservation->getPassengersCount(),
                $reservation->getStatus()
            ));
        }

        return Command::SUCCESS;
    }
}
