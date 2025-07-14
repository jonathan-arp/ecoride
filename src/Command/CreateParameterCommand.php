<?php

namespace App\Command;

use App\Entity\Parameter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateParameterCommand extends Command
{
    protected static $defaultName = 'app:create-parameters';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('app:create-parameters')
            ->setDescription('Creates default user parameters');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if parameters already exist
        $existingParams = $this->entityManager->getRepository(Parameter::class)->findAll();
        if (count($existingParams) > 0) {
            $io->warning('Parameters already exist. Skipping creation.');
            return Command::SUCCESS;
        }

        $parameters = [
            ['name' => 'Fumeur', 'value' => 'Oui', 'icon' => 'fa-smoking'],
            ['name' => 'Non-fumeur', 'value' => 'Non', 'icon' => 'fa-smoking-ban'],
            ['name' => 'Avec animaux', 'value' => 'Accepté', 'icon' => 'fa-paw'],
            ['name' => 'Sans animaux', 'value' => 'Refusé', 'icon' => 'fa-ban'],
            ['name' => 'Avec enfants', 'value' => 'Accepté', 'icon' => 'fa-child'],
            ['name' => 'Sans enfants', 'value' => 'Refusé', 'icon' => 'fa-user-times'],
            ['name' => 'Musique autorisée', 'value' => 'Oui', 'icon' => 'fa-music'],
            ['name' => 'Voyage silencieux', 'value' => 'Préféré', 'icon' => 'fa-volume-off'],
            ['name' => 'Conversation', 'value' => 'Aimé', 'icon' => 'fa-comments'],
            ['name' => 'Lecture pendant trajet', 'value' => 'Préféré', 'icon' => 'fa-book'],
            ['name' => 'Arrêts fréquents', 'value' => 'Accepté', 'icon' => 'fa-pause'],
            ['name' => 'Trajet direct', 'value' => 'Préféré', 'icon' => 'fa-arrow-right'],
        ];

        foreach ($parameters as $paramData) {
            $parameter = new Parameter();
            $parameter->setName($paramData['name']);
            $parameter->setValue($paramData['value']);
            $parameter->setIcon($paramData['icon']);
            
            $this->entityManager->persist($parameter);
        }

        $this->entityManager->flush();

        $io->success('Default parameters created successfully.');
        $io->note('Created ' . count($parameters) . ' parameters.');

        return Command::SUCCESS;
    }
}
