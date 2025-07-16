<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\PasswordUserType;
use App\Form\AccountType;
use Doctrine\ORM\EntityManagerInterface;


final class AccountController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/account', name: 'app_account')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à votre compte.');
            return $this->redirectToRoute('app_login');
        }

        // Recharge l'utilisateur depuis la base
        $userId = $user->getId();
        $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);

        if (!$freshUser) {
            $this->addFlash('info', 'Votre session a expiré. Veuillez vous reconnecter.');
            return $this->redirectToRoute('app_logout');
        }

        // Calcul de la note moyenne
        $averageRating = method_exists($freshUser, 'getAverageRating') ? $freshUser->getAverageRating() : null;

        // Calcul du nombre d'avis publiés
        $reviewsCount = method_exists($freshUser, 'getPublishedReviewsCount') ? $freshUser->getPublishedReviewsCount() : 0;

        // Détacher l'entité pour éviter les conflits lors de navigation rapide
        $this->entityManager->detach($freshUser);

        return $this->render('account/index.html.twig', [
            'controller_name' => 'AccountController',
            'user' => $freshUser,
            'averageRating' => $averageRating,
            'reviewsCount' => $reviewsCount,
        ]);
    }

    #[Route('/compte/modifier-mot-de-passe', name: 'app_account_modify_pwd')]
    public function password(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            $this->addFlash('danger', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Get a fresh copy of the user to avoid stale entity issues during development
            $userId = $user->getId();
          
            $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
            
            if (!$freshUser) {
                $this->addFlash('info', 'Votre session a expiré. Veuillez vous reconnecter.');
                return $this->redirectToRoute('app_logout');
            }

            $form = $this->createForm(PasswordUserType::class, $freshUser, ['passwordHasher' => $passwordHasher]);
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid())
            {
                $entityManager->flush();
                $this->addFlash('success', 'Votre mot de passe a été modifié avec succès');
                return $this->redirectToRoute('app_account');
            }
            
            // Détacher l'entité pour éviter les conflits
            $this->entityManager->detach($freshUser);
            
            return $this->render('account/password.html.twig', ['modifyPwdForm' => $form->createView()]);
        } catch (\Exception $e) {
            error_log('Password change error: ' . $e->getMessage());
            if (strpos($e->getMessage(), 'serialize') !== false) {
                $this->addFlash('info', 'Votre session a été corrompue. Veuillez vous reconnecter.');
                return $this->redirectToRoute('app_logout');
            }
            $this->addFlash('danger', 'Une erreur est survenue. Veuillez réessayer.');
            return $this->redirectToRoute('app_account');
        }
    }

    #[Route('/compte/profil', name: 'app_account_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            $this->addFlash('danger', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Get a fresh copy of the user to avoid stale entity issues during development
            $userId = $user->getId();
           
            $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
            
            if (!$freshUser) {
                $this->addFlash('info', 'Votre session a expiré. Veuillez vous reconnecter.');
                return $this->redirectToRoute('app_logout');
            }

            $form = $this->createForm(AccountType::class, $freshUser);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->flush();
                $this->addFlash('success', 'Votre profil a été modifié avec succès');
                return $this->redirectToRoute('app_account');
            }

            // Détacher l'entité pour éviter les conflits
            $this->entityManager->detach($freshUser);

            return $this->render('account/profile.html.twig', [
                'profileForm' => $form->createView(),
                'user' => $freshUser
            ]);
        } catch (\Exception $e) {
            error_log('Profile error: ' . $e->getMessage());
            if (strpos($e->getMessage(), 'serialize') !== false) {
                $this->addFlash('info', 'Votre session a été corrompue. Veuillez vous reconnecter.');
                return $this->redirectToRoute('app_logout');
            }
            $this->addFlash('danger', 'Une erreur est survenue. Veuillez réessayer.');
            return $this->redirectToRoute('app_account');
        }
    }
}
