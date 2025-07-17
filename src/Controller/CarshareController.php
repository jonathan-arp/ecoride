<?php

namespace App\Controller;

use App\Entity\Carshare;
use App\Form\CarshareSearchType;
use App\Form\CarshareType;
use App\Repository\CarshareRepository;
use App\Repository\PlatformTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    public function index(): Response
    {
        // Per project requirements: no carshares should be visible by default
        // Redirect to search page where users must search first
        return $this->redirectToRoute('app_carshare_search');
    }

    #[Route('/carshares/{id}', name: 'app_carshare_by_id')]
    public function ficheCarshare($id, EntityManagerInterface $entityManager): Response
    {
        $carshare = $entityManager->getRepository(Carshare::class)->find($id);

        if (!$carshare) {
            throw $this->createNotFoundException('Carshare non trouvé');
        }

        // TODO: Remplacer par un cron job pour éviter les problèmes de navigation rapide
        // Check and mark if expired
        // $this->checkAndMarkExpiredCarshares([$carshare]);

        return $this->render('carshare/fiche_carshare.html.twig', [
            'carshare' => $carshare,
        ]);
    }

    #[Route('/carshare/search', name: 'app_carshare_search', methods: ['GET', 'POST'])]
    public function search(Request $request, CarshareRepository $carshareRepository): Response
    {
        $form = $this->createForm(CarshareSearchType::class);
        
        // Handle GET parameters for alternative date searches
        if ($request->query->has('departureLocation') && $request->query->has('arrivalLocation')) {
            $defaultData = [
                'departureLocation' => $request->query->get('departureLocation'),
                'arrivalLocation' => $request->query->get('arrivalLocation'),
                'date' => $request->query->has('date') ? new \DateTime($request->query->get('date')) : new \DateTime(),
                'passengers' => $request->query->get('passengers', 1)
            ];
            $form->setData($defaultData);
        }
        
        $form->handleRequest($request);
        
        $searchResults = [];
        $searchPerformed = false;
        $serializedResults = [];
        $alternativeDates = [];
        $searchCriteria = [];
        
        // Handle both POST (form submission) and GET (alternative date clicks)
        if ($form->isSubmitted() || $request->query->has('departureLocation')) {
            $data = $form->isSubmitted() ? $form->getData() : [
                'departureLocation' => $request->query->get('departureLocation'),
                'arrivalLocation' => $request->query->get('arrivalLocation'),
                'date' => new \DateTime($request->query->get('date')),
                'passengers' => (int) $request->query->get('passengers', 1)
            ];
            
            if (!$form->isSubmitted() || $form->isValid()) {
                $searchResults = $carshareRepository->searchCarshares(
                    $data['departureLocation'],
                    $data['arrivalLocation'],
                    $data['date'],
                    $data['passengers']
                );

                // TODO: Remplacer par un cron job pour éviter les problèmes de navigation rapide
                // Check and mark expired carshares
                // $this->checkAndMarkExpiredCarshares($searchResults);

                // If no results found, look for alternative dates
                $alternativeDates = [];
                if (empty($searchResults)) {
                    $alternativeDateCarshares = $carshareRepository->findAlternativeDatesForRoute(
                        $data['departureLocation'],
                        $data['arrivalLocation'],
                        $data['passengers'],
                        $data['date']
                    );
                    
                    // Group by date and get the closest dates
                    $dateGroups = [];
                    foreach ($alternativeDateCarshares as $carshare) {
                        $dateKey = $carshare->getStart()->format('Y-m-d');
                        if (!isset($dateGroups[$dateKey])) {
                            $dateGroups[$dateKey] = [
                                'date' => $carshare->getStart(),
                                'count' => 0
                            ];
                        }
                        $dateGroups[$dateKey]['count']++;
                    }
                    $alternativeDates = array_values($dateGroups);
                }
                
                // Serialize search results for React component
                foreach ($searchResults as $carshare) {
                    $serializedResults[] = [
                        'id' => $carshare->getId(),
                        'start' => $carshare->getStart()->format('Y-m-d H:i:s'),
                        'end' => $carshare->getEnd()->format('Y-m-d H:i:s'),
                        'startLocation' => $carshare->getStartLocation(),
                        'endLocation' => $carshare->getEndLocation(),
                        'startDetail' => $carshare->getStartDetail(),
                        'endDetail' => $carshare->getEndDetail(),
                        'formattedRoute' => $carshare->getFormattedRoute(),
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
                
                $this->addFlash('success', 'Recherche effectuée pour ' . count($searchResults) . ' résultat(s).');
                
                // For form submissions, use redirect pattern; for GET requests, display directly
                if ($form->isSubmitted()) {
                    $request->getSession()->set('search_results', $searchResults);
                    $request->getSession()->set('serialized_results', $serializedResults);
                    $request->getSession()->set('alternative_dates', $alternativeDates);
                    $request->getSession()->set('search_criteria', $data);
                    $request->getSession()->set('search_performed', true);
                    
                    return $this->redirectToRoute('app_carshare_search');
                } else {
                    // For GET requests, set variables directly
                    $searchCriteria = $data;
                    $searchPerformed = true;
                }
            } else {
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
            }
        }
        
        // Check if we have search results from a redirect
        if ($request->getSession()->has('search_results')) {
            $searchResults = $request->getSession()->get('search_results');
            $serializedResults = $request->getSession()->get('serialized_results', []);
            $alternativeDates = $request->getSession()->get('alternative_dates', []);
            $searchCriteria = $request->getSession()->get('search_criteria', []);
            $searchPerformed = $request->getSession()->get('search_performed', false);
            
            // Clear the session data
            $request->getSession()->remove('search_results');
            $request->getSession()->remove('serialized_results');
            $request->getSession()->remove('alternative_dates');
            $request->getSession()->remove('search_criteria');
            $request->getSession()->remove('search_performed');
        }
        
        return $this->render('carshare/search.html.twig', [
            'searchForm' => $form->createView(),
            'searchResults' => $serializedResults, // Pass serialized results for React
            'alternativeDates' => $alternativeDates ?? [],
            'searchCriteria' => $searchCriteria ?? [],
            'searchPerformed' => $searchPerformed,
        ]);
    }
    
    #[Route('/carshare/new', name: 'app_carshare_new')]
    public function new(Request $request): Response
    {
        $sessionUser = $this->getUser();
        
        // Vérifier si l'utilisateur a le droit de créer un covoiturage
        if (!$sessionUser instanceof \App\Entity\User || !$sessionUser->canDrive()) {
            $this->addFlash('error', 'Vous devez être déclaré comme conducteur ou conducteur/passager pour créer un covoiturage.');
            return $this->redirectToRoute('app_account_profile');
        }

        // Utiliser une entité fraîche pour éviter la corruption de session
        $userId = $sessionUser->getId();
        $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        
        if (!$freshUser) {
            return $this->redirectToRoute('app_login');
        }
        
        // Vérifier si l'utilisateur a au moins une voiture
        if ($freshUser->getCars()->isEmpty()) {
            $this->addFlash('error', 'Vous devez d\'abord ajouter une voiture pour créer un covoiturage.');
            // Détacher l'entité pour éviter les problèmes de navigation rapide
            // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);
            return $this->redirectToRoute('app_car_new');
        }
        
        $carshare = new Carshare();
        $carshare->setDriver($freshUser);
        
        $form = $this->createForm(CarshareType::class, $carshare);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($carshare);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre covoiturage a été créé avec succès !');
            
            // Détacher l'entité pour éviter les problèmes de navigation rapide
            // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);
            
            return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
        }

        // For Turbo compatibility, when form has errors, return proper response
        $response = $this->render('carshare/new.html.twig', [
            'form' => $form->createView(),
        ]);

        // Set proper status code for form errors
        if ($form->isSubmitted() && !$form->isValid()) {
            $response->setStatusCode(422); // Unprocessable Entity
        }

        // Détacher l'entité pour éviter les problèmes de navigation rapide
        // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);

        return $response;

        // Set proper status code for form errors
        if ($form->isSubmitted() && !$form->isValid()) {
            $response->setStatusCode(422); // Unprocessable Entity
        }

        return $response;
    }

    #[Route('/carshare/my', name: 'app_carshare_my')]
    public function myCarshares(): Response
    {
        $sessionUser = $this->getUser();
        
        if (!$sessionUser instanceof \App\Entity\User) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        // Utiliser une entité fraîche pour éviter la corruption de session
        $userId = $sessionUser->getId();
        $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        
        if (!$freshUser) {
            return $this->redirectToRoute('app_login');
        }

        $carshareRepository = $this->entityManager->getRepository(Carshare::class);
        
        // Obtenir les covoiturages en tant que conducteur
        $asDriver = [];
        if ($freshUser->canDrive()) {
            $asDriver = $carshareRepository->findBy(['driver' => $freshUser]);
            // TODO: Remplacer par un cron job pour éviter les problèmes de navigation rapide
            // Check and mark expired carshares
            // $this->checkAndMarkExpiredCarshares($asDriver);
        }

        // Détacher l'entité pour éviter les problèmes de navigation rapide
        // NOTE: Ne jamais d�tacher l'entit� User car cela peut affecter la session de s�curit�`r`n        // $this->entityManager->detach($freshUser);

        return $this->render('carshare/my_carshares.html.twig', [
            'asDriver' => $asDriver,
            'user' => $freshUser,
        ]);
    }
    
    #[Route('/carshare/{id}/edit', name: 'app_carshare_edit')]
    public function edit(Request $request, Carshare $carshare): Response
    {
        $sessionUser = $this->getUser();
        
        // Vérifier que l'utilisateur est le conducteur de ce covoiturage
        if (!$sessionUser instanceof \App\Entity\User || $carshare->getDriver()->getId() !== $sessionUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres covoiturages.');
            return $this->redirectToRoute('app_carshare_my');
        }
        
        $form = $this->createForm(CarshareType::class, $carshare);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre covoiturage a été modifié avec succès !');
            return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
        }

        return $this->render('carshare/edit.html.twig', [
            'form' => $form->createView(),
            'carshare' => $carshare,
        ]);
    }
    
    #[Route('/carshare/{id}/delete', name: 'app_carshare_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Carshare $carshare): Response
    {
        $sessionUser = $this->getUser();
        
        // Vérifier que l'utilisateur est le conducteur de ce covoiturage
        if (!$sessionUser instanceof \App\Entity\User || $carshare->getDriver()->getId() !== $sessionUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres covoiturages.');
            return $this->redirectToRoute('app_carshare_my');
        }
        
        // Vérifier le token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete'.$carshare->getId(), $request->request->get('_token'))) {
            
            // Gérer les réservations existantes
            $reservationsCount = $carshare->getReservations()->count();
            if ($reservationsCount > 0) {
                // Annuler toutes les réservations et leurs transactions
                $platformTransactionRepository = $this->entityManager->getRepository(\App\Entity\PlatformTransaction::class);
                
                foreach ($carshare->getReservations() as $reservation) {
                    // Supprimer la transaction en attente associée
                    $pendingTransaction = $platformTransactionRepository->findPendingByReservation($reservation);
                    if ($pendingTransaction) {
                        $this->entityManager->remove($pendingTransaction);
                    }
                    
                    // Supprimer la réservation
                    $this->entityManager->remove($reservation);
                }
            }
            
            // Supprimer les crédits liés à ce covoiturage
            $creditRepository = $this->entityManager->getRepository(\App\Entity\Credit::class);
            $relatedCredits = $creditRepository->findBy(['carshare' => $carshare]);
            foreach ($relatedCredits as $credit) {
                $this->entityManager->remove($credit);
            }
            
            // Maintenant on peut supprimer le covoiturage
            $this->entityManager->remove($carshare);
            $this->entityManager->flush();
            
            if ($reservationsCount > 0) {
                $this->addFlash('success', sprintf(
                    'Votre covoiturage a été supprimé avec succès. %d réservation%s ont été automatiquement annulée%s.',
                    $reservationsCount,
                    $reservationsCount > 1 ? 's' : '',
                    $reservationsCount > 1 ? 's' : ''
                ));
            } else {
                $this->addFlash('success', 'Votre covoiturage a été supprimé avec succès.');
            }
        } else {
            $this->addFlash('error', 'Token de sécurité invalide.');
        }
        
        return $this->redirectToRoute('app_carshare_my');
    }

    private function checkAndMarkExpiredCarshares(array $carshares): void
    {
        $modified = false;
        foreach ($carshares as $carshare) {
            if ($carshare->isExpired() && $carshare->getStatus() !== 'EXPIRED') {
                $carshare->markAsExpired();
                $this->entityManager->persist($carshare);
                $modified = true;
            }
        }
        if ($modified) {
            $this->entityManager->flush();
        }
    }

}
