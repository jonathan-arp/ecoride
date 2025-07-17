<?php

namespace App\Controller;

use App\Entity\Car;
use App\Form\CarType;
use App\Repository\CarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/car')]
#[IsGranted('ROLE_USER')]
final class CarController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CarRepository $carRepository
    ) {
    }

    #[Route('/', name: 'app_car_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        // Get a fresh copy of the user to avoid stale entity issues
        $userId = $user->getId();
        $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        
        if (!$freshUser) {
            return $this->redirectToRoute('app_login');
        }

        $cars = $this->carRepository->findBy(['user' => $freshUser]);

        // Détacher l'entité pour éviter les conflits lors de navigation rapide
        // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);

        return $this->render('car/index.html.twig', [
            'cars' => $cars,
        ]);
    }

    #[Route('/new', name: 'app_car_new')]
    public function new(Request $request): Response
    {
        $sessionUser = $this->getUser();
        
        // Vérifier si l'utilisateur a le droit d'ajouter une voiture
        if (!$sessionUser instanceof \App\Entity\User || !$sessionUser->canDrive()) {
            $this->addFlash('error', 'Vous devez être déclaré comme conducteur ou conducteur/passager pour ajouter une voiture.');
            return $this->redirectToRoute('app_account_profile');
        }
        
        // Utiliser une entité fraîche pour éviter la corruption de session
        $userId = $sessionUser->getId();
        $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        
        if (!$freshUser) {
            return $this->redirectToRoute('app_login');
        }
        
        $car = new Car();
        $car->setUser($freshUser);
        
        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($car);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre véhicule a été ajouté avec succès !');
            return $this->redirectToRoute('app_car_index');
        }

        // Détacher l'entité pour éviter les conflits
        // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);

        return $this->render('car/new.html.twig', [
            'car' => $car,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_car_show', requirements: ['id' => '\d+'])]
    public function show(Car $car): Response
    {
        $this->checkCarOwnership($car);

        return $this->render('car/show.html.twig', [
            'car' => $car,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_car_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Car $car): Response
    {
        $this->checkCarOwnership($car);

        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre véhicule a été modifié avec succès !');
            return $this->redirectToRoute('app_car_index');
        }

        return $this->render('car/edit.html.twig', [
            'car' => $car,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_car_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Car $car): Response
    {
        $this->checkCarOwnership($car);

        if ($this->isCsrfTokenValid('delete'.$car->getId(), $request->getPayload()->get('_token'))) {
            $this->entityManager->remove($car);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre véhicule a été supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('app_car_index');
    }

    private function checkCarOwnership(Car $car): void
    {
        $sessionUser = $this->getUser();
        if (!$sessionUser instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        
        // Utiliser l'ID pour la comparaison afin d'éviter les problèmes d'entité en session
        if ($car->getUser()->getId() !== $sessionUser->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à ce véhicule.');
        }
    }
}
