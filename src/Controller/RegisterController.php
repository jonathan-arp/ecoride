<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Credit;
use App\Form\RegisterUserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegisterUserType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Hash the password
                $plaintextPassword = $form->get('plainPassword')->getData();
                $hashedPassword = $passwordHasher->hashPassword($user, $plaintextPassword);
                $user->setPassword($hashedPassword);
                
                // Set default role
                $user->setRoles(['ROLE_USER']);
                
                // Set default photo
                $user->setPhoto('default.jpg');
                
                // Save the user first
                $entityManager->persist($user);
                $entityManager->flush();
                
                // Create welcome credit of 20 credits
                $welcomeCredit = new Credit();
                $welcomeCredit->setUser($user);
                $welcomeCredit->setAmount(20.0);
                $welcomeCredit->setType('WELCOME');
                $welcomeCredit->setDescription('Crédits de bienvenue');
                
                // Save the welcome credit
                $entityManager->persist($welcomeCredit);
                $entityManager->flush();
                
                $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous avez reçu 20 crédits de bienvenue. Vous pouvez maintenant vous connecter.');
                
                // Redirect to avoid Turbo form submission error
                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
            }
        }
        
        return $this->render('register/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
