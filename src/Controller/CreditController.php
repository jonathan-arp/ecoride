<?php

namespace App\Controller;

use App\Repository\CreditRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreditController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/account/credits', name: 'app_credits_index')]
    public function index(CreditRepository $creditRepository): Response
    {
        $sessionUser = $this->getUser();

        if (!$sessionUser instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        // Utiliser une entité fraîche pour éviter la corruption de session
        $userId = $sessionUser->getId();
        $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        
        if (!$freshUser) {
            return $this->redirectToRoute('app_login');
        }

        $credits = $creditRepository->findByUser($freshUser);
        $balance = $freshUser->getCreditBalance();

        // Détacher l'entité pour éviter les conflits lors de navigation rapide
        $this->entityManager->detach($freshUser);

        return $this->render('credit/index.html.twig', [
            'credits' => $credits,
            'balance' => $balance,
        ]);
    }

    #[Route('/account/credits/purchase', name: 'app_credits_purchase', methods: ['GET', 'POST'])]
    public function purchase(Request $request): Response
    {
        $sessionUser = $this->getUser();

        if (!$sessionUser instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $amount = (float) $request->request->get('amount');
            
            // Validation basique
            if ($amount <= 0 || $amount > 1000) {
                $this->addFlash('error', 'Le montant doit être entre 1 et 1000 crédits.');
                return $this->render('credit/purchase.html.twig');
            }

            // Vérifier le token CSRF
            if (!$this->isCsrfTokenValid('purchase_credits', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->render('credit/purchase.html.twig');
            }

            // Utiliser une entité fraîche pour éviter la corruption de session
            $userId = $sessionUser->getId();
            $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
            
            if (!$freshUser) {
                return $this->redirectToRoute('app_login');
            }

            // Simulation d'achat (pas de vraie transaction)
            $credit = $freshUser->addCredits($amount, 'PURCHASE', sprintf('Achat de %.2f crédits (simulation)', $amount));
            $this->entityManager->persist($credit);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'Achat simulé réussi ! %.2f crédits ajoutés. Nouveau solde: %.2f crédits.',
                $amount,
                $freshUser->getCreditBalance()
            ));

            return $this->redirectToRoute('app_credits_index');
        }

        return $this->render('credit/purchase.html.twig');
    }
}
