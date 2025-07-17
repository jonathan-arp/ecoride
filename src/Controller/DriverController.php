<?php

namespace App\Controller;

use App\Entity\Parameter;
use App\Entity\User;
use App\Form\ParameterType;
use App\Form\UserPhotoType;
use App\Repository\ParameterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/account/driver')]
class DriverController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/parameters', name: 'app_driver_parameters')]
    public function parameters(ParameterRepository $parameterRepository): Response
    {
        $sessionUser = $this->getUser();
        if (!$sessionUser || !$sessionUser instanceof \App\Entity\User) {
            $this->addFlash('danger', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }
        
        // Utiliser une entité fraîche pour éviter la corruption de session
        $userId = $sessionUser->getId();
        $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        
        if (!$freshUser) {
            return $this->redirectToRoute('app_login');
        }
        
        $userParameters = $freshUser->getParameters();
        $allParameters = $parameterRepository->findAll();

        // NOTE: Ne jamais détacher l'entité User car cela peut affecter la session de sécurité
        // // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);

        return $this->render('account/driver/parameters.html.twig', [
            'userParameters' => $userParameters,
            'allParameters' => $allParameters,
        ]);
    }

    #[Route('/parameters/{id}/toggle', name: 'app_driver_parameter_toggle', methods: ['POST'])]
    public function toggleParameter(Parameter $parameter, EntityManagerInterface $entityManager): Response
    {
        $sessionUser = $this->getUser();
        if (!$sessionUser || !$sessionUser instanceof \App\Entity\User) {
            $this->addFlash('danger', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        // Utiliser une entité fraîche pour éviter la corruption de session
        $userId = $sessionUser->getId();
        $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        
        if (!$freshUser) {
            return $this->redirectToRoute('app_login');
        }

        if ($freshUser->getParameters()->contains($parameter)) {
            $freshUser->removeParameter($parameter);
            $this->addFlash('success', 'Paramètre "' . $parameter->getName() . '" retiré de vos préférences.');
        } else {
            $freshUser->addParameter($parameter);
            $this->addFlash('success', 'Paramètre "' . $parameter->getName() . '" ajouté à vos préférences.');
        }

        $entityManager->flush();
        
        // Détacher l'entité pour éviter les problèmes de navigation rapide
        // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);

        return $this->redirectToRoute('app_driver_parameters');
    }

    #[Route('/parameters/new', name: 'app_driver_parameter_new')]
    public function newParameter(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parameter = new Parameter();
        $form = $this->createForm(ParameterType::class, $parameter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($parameter);
            
            // Automatically add the new parameter to the current user
            $sessionUser = $this->getUser();
            if (!$sessionUser || !$sessionUser instanceof \App\Entity\User) {
                $this->addFlash('danger', 'Vous devez être connecté.');
                return $this->redirectToRoute('app_login');
            }
            
            // Utiliser une entité fraîche pour éviter la corruption de session
            $userId = $sessionUser->getId();
            $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
            
            if (!$freshUser) {
                return $this->redirectToRoute('app_login');
            }
            
            $freshUser->addParameter($parameter);
            
            $entityManager->flush();

            $this->addFlash('success', 'Nouveau paramètre "' . $parameter->getName() . '" créé et ajouté à vos préférences.');

            // Détacher l'entité pour éviter les problèmes de navigation rapide
            // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);

            return $this->redirectToRoute('app_driver_parameters');
        }

        return $this->render('account/driver/parameter_new.html.twig', [
            'parameter' => $parameter,
            'form' => $form,
        ]);
    }

    #[Route('/photo', name: 'app_driver_photo', methods: ['GET', 'POST'])]
    public function uploadPhoto(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sessionUser = $this->getUser();
        if (!$sessionUser || !$sessionUser instanceof \App\Entity\User) {
            $this->addFlash('danger', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        // Utiliser une entité fraîche pour éviter la corruption de session
        $userId = $sessionUser->getId();
        $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        
        if (!$freshUser) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user has at least one car
        if ($freshUser->getCars()->isEmpty()) {
            $this->addFlash('warning', 'Vous devez d\'abord enregistrer un véhicule pour accéder à cette fonctionnalité.');
            // Détacher l'entité pour éviter les problèmes de navigation rapide
            // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);
            return $this->redirectToRoute('app_car_new');
        }

        $form = $this->createForm(UserPhotoType::class, $freshUser);
        
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $entityManager->flush();
                    $this->addFlash('success', 'Votre photo de profil a été mise à jour avec succès.');
                    
                    // Détacher l'entité pour éviter les problèmes de navigation rapide
                    // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);
                    
                    // Redirect to avoid Turbo form submission error and clear any potential serialization issues
                    return $this->redirectToRoute('app_driver_photo');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload : ' . $e->getMessage());
                    // Détacher l'entité pour éviter les problèmes de navigation rapide
                    // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);
                }
            } else {
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
                // Détacher l'entité pour éviter les problèmes de navigation rapide
                // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);
            }
        }

        // Détacher l'entité pour éviter les problèmes de navigation rapide
        // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);

        return $this->render('account/driver/photo.html.twig', [
            'form' => $form->createView(),
            'user' => $freshUser
        ]);
    }
}
