<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Fonction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:assign-default-fonction',
    description: 'Assign default fonction to existing users without fonction',
)]
class AssignDefaultFonctionCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get default fonction (Conducteur et Passager)
        $defaultFonction = $this->entityManager
            ->getRepository(Fonction::class)
            ->findOneBy(['code' => 'DRIVER_PASSENGER']);

        if (!$defaultFonction) {
            $io->error('Default fonction "Conducteur et Passager" not found. Please run app:create-fonction first.');
            return Command::FAILURE;
        }

        // Get users without fonction
        $usersWithoutFonction = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.fonction IS NULL')
            ->getQuery()
            ->getResult();

        if (empty($usersWithoutFonction)) {
            $io->success('All users already have a fonction assigned!');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($usersWithoutFonction as $user) {
            $user->setFonction($defaultFonction);
            $count++;
            $io->info('Assigned default fonction to user: ' . $user->getEmail());
        }

        $this->entityManager->flush();

        $io->success("Successfully assigned default fonction to {$count} users!");

        return Command::SUCCESS;
    }
}
