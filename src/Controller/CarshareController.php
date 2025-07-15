<?php

namespace App\Controller;

use App\Entity\Carshare;
use App\Repository\CarshareRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CarshareController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/carshares', name: 'app_carshare')]
    public function index(CarshareRepository $carshareRepository): Response
    {
        $carshares = $carshareRepository->findAll();

        // Serialize carshares to pass to React
        $serializedCarshares = [];
        foreach ($carshares as $carshare) {
            $serializedCarshares[] = [
                'id' => $carshare->getId(),
                'start' => $carshare->getStart()->format('Y-m-d H:i:s'),
                'end' => $carshare->getEnd()->format('Y-m-d H:i:s'),
                'startLocation' => $carshare->getStartLocation(),
                'endLocation' => $carshare->getEndLocation(),
                'status' => $carshare->getStatus(),
                'place' => $carshare->getPlace(),
                'price' => number_format($carshare->getPrice(), 2, '.', ''),
                'driver' => [
                    'id' => $carshare->getDriver()->getId(),
                    'firstname' => $carshare->getDriver()->getFirstname(),
                    'lastname' => $carshare->getDriver()->getLastname(),
                ],
                'car' => [
                    'id' => $carshare->getCar()->getId(),
                    'model' => $carshare->getCar()->getModel(),
                    'color' => $carshare->getCar()->getColor(),
                    'brand' => $carshare->getCar()->getBrand()->getContent(),
                    'energyType' => $carshare->getCar()->getEnergyType()?->value,
                ],
            ];
        }

        
        return $this->render('carshare/index.html.twig', [
            'carshares' => $serializedCarshares
        ]);
    }

    #[Route('/carshares/{id}', name: 'app_carshare_by_id')]
    public function ficheCarshare($id, EntityManagerInterface $entityManager): Response
    {
        $carshare = $entityManager->getRepository(Carshare::class)->find($id);

        if (!$carshare) {
            throw $this->createNotFoundException('Carshare non trouvé');
        }

        return $this->render('carshare/fiche_carshare.html.twig', [
            'carshare' => $carshare,
        ]);
    }
    
}
