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
use Psr\Log\LoggerInterface;


final class AccountController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    #[Route('/account', name: 'app_account')]
    public function index(): Response
    {
        $user = $this->getUser();
        $this->logger->info('[AccountController] index() - User from getUser()', [
            'user_exists' => $user ? 'OUI' : 'NON',
            'user_class' => $user ? get_class($user) : null,
            'user_id' => ($user instanceof \App\Entity\User) ? $user->getId() : null
        ]);

        if (!$user || !$user instanceof \App\Entity\User) {
            $this->logger->warning('[AccountController] User not valid, redirecting to login');
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à votre compte.');
            return $this->redirectToRoute('app_login');
        }

        // Recharge l'utilisateur depuis la base
        $userId = $user->getId();
        $this->logger->info('[AccountController] Searching for user in database', ['user_id' => $userId]);
        
        $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);

        $this->logger->info('[AccountController] Fresh user from database', [
            'fresh_user_exists' => $freshUser ? 'OUI' : 'NON',
            'fresh_user_id' => $freshUser ? $freshUser->getId() : null
        ]);

        if (!$freshUser) {
            $this->logger->warning('[AccountController] Fresh user not found, logging out');
            $this->addFlash('info', 'Votre session a expiré. Veuillez vous reconnecter.');
            return $this->redirectToRoute('app_logout');
        }

        // Calcul de la note moyenne
        $averageRating = method_exists($freshUser, 'getAverageRating') ? $freshUser->getAverageRating() : null;

        // Calcul du nombre d'avis publiés
        $reviewsCount = method_exists($freshUser, 'getPublishedReviewsCount') ? $freshUser->getPublishedReviewsCount() : 0;

        // NOTE: Ne jamais détacher l'entité User car cela peut affecter la session de sécurité
        // $this->entityManager->detach($freshUser);

        return $this->render('account/index.html.twig', [
            'controller_name' => 'AccountController',
            'user' => $freshUser,
            'averageRating' => $averageRating,
            'reviewsCount' => $reviewsCount,
        ]);
    }

    #[Route('/compte/modifier-mot-de-passe', name: 'app_account_modify_pwd')]
    public function password(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        $this->logger->info('[AccountController] password() - User from getUser()', [
            'user_exists' => $user ? 'OUI' : 'NON',
            'user_class' => $user ? get_class($user) : null,
            'user_id' => ($user instanceof \App\Entity\User) ? $user->getId() : null
        ]);

        if (!$user || !$user instanceof \App\Entity\User) {
            $this->logger->warning('[AccountController] password() - User not valid, redirecting to login');
            $this->addFlash('danger', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Get a fresh copy of the user to avoid stale entity issues during development
            $userId = $user->getId();
            $this->logger->info('[AccountController] password() - Searching for user in database', ['user_id' => $userId]);
          
            $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
            
            $this->logger->info('[AccountController] password() - Fresh user from database', [
                'fresh_user_exists' => $freshUser ? 'OUI' : 'NON',
                'fresh_user_id' => $freshUser ? $freshUser->getId() : null
            ]);
            
            if (!$freshUser) {
                $this->logger->warning('[AccountController] password() - Fresh user not found, logging out');
                $this->addFlash('info', 'Votre session a expiré. Veuillez vous reconnecter.');
                return $this->redirectToRoute('app_logout');
            }

            $form = $this->createForm(PasswordUserType::class, $freshUser, ['passwordHasher' => $passwordHasher]);
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid())
            {
                $this->entityManager->flush();
                $this->addFlash('success', 'Votre mot de passe a été modifié avec succès');
                return $this->redirectToRoute('app_account');
            }
            
            // NOTE: Ne jamais détacher l'entité User car cela peut affecter la session de sécurité
            // $this->entityManager->detach($freshUser);
            
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
    public function profile(Request $request): Response
    {
        $user = $this->getUser();
        $this->logger->info('[AccountController] profile() - User from getUser()', [
            'user_exists' => $user ? 'OUI' : 'NON',
            'user_class' => $user ? get_class($user) : null,
            'user_id' => ($user instanceof \App\Entity\User) ? $user->getId() : null
        ]);

        if (!$user || !$user instanceof \App\Entity\User) {
            $this->logger->warning('[AccountController] profile() - User not valid, redirecting to login');
            $this->addFlash('danger', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Get a fresh copy of the user to avoid stale entity issues during development
            $userId = $user->getId();
            $this->logger->info('[AccountController] profile() - Searching for user in database', ['user_id' => $userId]);
           
            $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
            
            $this->logger->info('[AccountController] profile() - Fresh user from database', [
                'fresh_user_exists' => $freshUser ? 'OUI' : 'NON',
                'fresh_user_id' => $freshUser ? $freshUser->getId() : null
            ]);
            
            if (!$freshUser) {
                $this->logger->warning('[AccountController] profile() - Fresh user not found, logging out');
                $this->addFlash('info', 'Votre session a expiré. Veuillez vous reconnecter.');
                return $this->redirectToRoute('app_logout');
            }

            $form = $this->createForm(AccountType::class, $freshUser);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->entityManager->flush();
                $this->addFlash('success', 'Votre profil a été modifié avec succès');
                return $this->redirectToRoute('app_account');
            }

            // NOTE: Ne jamais détacher l'entité User car cela peut affecter la session de sécurité
            // $this->entityManager->detach($freshUser);

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
