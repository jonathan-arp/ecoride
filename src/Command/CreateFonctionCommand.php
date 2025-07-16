<?php

namespace App\Command;

use App\Entity\Fonction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-fonction',
    description: 'Create default fonction types for users',
)]
class CreateFonctionCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $fonctions = [
            [
                'name' => 'Passager uniquement',
                'code' => 'PASSENGER_ONLY',
                'description' => 'Utilisateur qui ne peut être que passager dans les covoiturages',
                'canDrive' => false,
                'canBePassenger' => true,
            ],
            [
                'name' => 'Conducteur uniquement',
                'code' => 'DRIVER_ONLY',
                'description' => 'Utilisateur qui ne peut être que conducteur de covoiturages',
                'canDrive' => true,
                'canBePassenger' => false,
            ],
            [
                'name' => 'Conducteur et Passager',
                'code' => 'DRIVER_PASSENGER',
                'description' => 'Utilisateur qui peut être à la fois conducteur et passager',
                'canDrive' => true,
                'canBePassenger' => true,
            ],
        ];

        foreach ($fonctions as $fonctionData) {
            // Check if fonction already exists
            $existingFonction = $this->entityManager
                ->getRepository(Fonction::class)
                ->findOneBy(['code' => $fonctionData['code']]);

            if (!$existingFonction) {
                $fonction = new Fonction();
                $fonction->setName($fonctionData['name']);
                $fonction->setCode($fonctionData['code']);
                $fonction->setDescription($fonctionData['description']);
                $fonction->setCanDrive($fonctionData['canDrive']);
                $fonction->setCanBePassenger($fonctionData['canBePassenger']);

                $this->entityManager->persist($fonction);
                $io->success('Created fonction: ' . $fonctionData['name']);
            } else {
                $io->info('Fonction already exists: ' . $fonctionData['name']);
            }
        }

        $this->entityManager->flush();

        $io->success('All default fonctions have been created or already exist!');

        return Command::SUCCESS;
    }
}
