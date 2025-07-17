<?php

namespace App\Command;

use App\Entity\Brand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateBrandCommand extends Command
{
    protected static $defaultName = 'app:create-brands';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('app:create-brands')
            ->setDescription('Creates default car brands');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if brands already exist
        $existingBrands = $this->entityManager->getRepository(Brand::class)->findAll();
        if (count($existingBrands) > 0) {
            $io->warning('Brands already exist. Skipping creation.');
            return Command::SUCCESS;
        }

        $brands = [
            // French brands
            'Peugeot',
            'Renault',
            'Citroën',
            'Alpine',
            'Bugatti',
            
            // German brands
            'Volkswagen',
            'BMW',
            'Mercedes-Benz',
            'Audi',
            'Porsche',
            'Opel',
            
            // Italian brands
            'Fiat',
            'Ferrari',
            'Lamborghini',
            'Alfa Romeo',
            'Maserati',
            
            // Japanese brands
            'Toyota',
            'Honda',
            'Nissan',
            'Mazda',
            'Subaru',
            'Mitsubishi',
            'Lexus',
            'Infiniti',
            
            // Korean brands
            'Hyundai',
            'Kia',
            
            // American brands
            'Ford',
            'Chevrolet',
            'Cadillac',
            'Tesla',
            
            // British brands
            'Mini',
            'Jaguar',
            'Land Rover',
            'Aston Martin',
            'Bentley',
            'Rolls-Royce',
            
            // Swedish brands
            'Volvo',
            'Saab',
            
            // Czech brands
            'Škoda',
            
            // Spanish brands
            'SEAT',
            
            // Other European brands
            'Dacia',
            'Lada',
        ];

        // Sort brands alphabetically for better organization
        sort($brands);

        foreach ($brands as $brandName) {
            $brand = new Brand();
            $brand->setContent($brandName);
            
            $this->entityManager->persist($brand);
        }

        $this->entityManager->flush();

        $io->success('Car brands created successfully.');
        $io->note('Created ' . count($brands) . ' car brands.');
        
        // Display the first few brands as confirmation
        $io->section('Sample brands created:');
        $sampleBrands = array_slice($brands, 0, 10);
        foreach ($sampleBrands as $brand) {
            $io->text('- ' . $brand);
        }
        if (count($brands) > 10) {
            $io->text('... and ' . (count($brands) - 10) . ' more brands.');
        }

        return Command::SUCCESS;
    }
}
