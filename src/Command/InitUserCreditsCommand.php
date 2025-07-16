<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-user-credits',
    description: 'Initialize credits for existing users',
)]
class InitUserCreditsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->entityManager->getRepository(User::class)->findAll();

        $io->progressStart(count($users));

        foreach ($users as $user) {
            // Vérifier si l'utilisateur a déjà des crédits d'inscription
            $hasInitialCredits = false;
            foreach ($user->getCredits() as $credit) {
                if ($credit->getType() === 'INITIAL') {
                    $hasInitialCredits = true;
                    break;
                }
            }

            if (!$hasInitialCredits) {
                $credit = $user->addCredits(20.0, 'INITIAL', 'Crédits d\'inscription offerts');
                $this->entityManager->persist($credit);
                $this->entityManager->persist($user);
            }

            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success('Crédits initialisés pour tous les utilisateurs !');

        return Command::SUCCESS;
    }
}
