<?php

namespace App\Controller;

use App\Entity\Carshare;
use App\Entity\Reservation;
use App\Entity\PlatformTransaction;
use App\Repository\ReservationRepository;
use App\Repository\PlatformTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReservationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/carshare/{id}/reserve', name: 'app_carshare_reserve', methods: ['POST'])]
    public function reserve(Request $request, Carshare $carshare): Response
    {
        $sessionUser = $this->getUser();

        if (!$sessionUser instanceof \App\Entity\User) {
            $this->addFlash('error', 'Vous devez être connecté pour réserver un covoiturage.');
            return $this->redirectToRoute('app_login');
        }

        // Utiliser une entité fraîche pour éviter la corruption de session
        $userId = $sessionUser->getId();
        $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        
        if (!$freshUser) {
            $this->addFlash('error', 'Erreur utilisateur.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifications
        if (!$carshare->canBeReservedBy($freshUser)) {
            $this->addFlash('error', 'Vous ne pouvez pas réserver ce covoiturage.');
            return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
        }

        // Récupérer le nombre de passagers depuis le formulaire
        $passengersCount = (int) $request->request->get('passengers_count', 1);
        
        // Vérifier que le nombre de passagers est valide
        if ($passengersCount < 1 || $passengersCount > $carshare->getAvailablePlaces()) {
            $this->addFlash('error', sprintf(
                'Nombre de passagers invalide. Disponible: %d place%s.',
                $carshare->getAvailablePlaces(),
                $carshare->getAvailablePlaces() > 1 ? 's' : ''
            ));
            return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
        }

        // Calculer le prix total
        $totalPrice = $carshare->getPrice() * $passengersCount;

        // Vérifier si l'utilisateur a assez de crédits
        if (!$freshUser->canAfford($totalPrice)) {
            $this->addFlash('error', sprintf(
                'Crédits insuffisants. Vous avez %.2f crédits, le coût total est %.2f crédits (%d passager%s × %.2f).',
                $freshUser->getCreditBalance(),
                $totalPrice,
                $passengersCount,
                $passengersCount > 1 ? 's' : '',
                $carshare->getPrice()
            ));
            return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('reserve'.$carshare->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
        }

        // Créer la réservation
        $reservation = new Reservation();
        $reservation->setPassenger($freshUser);
        $reservation->setCarshare($carshare);
        $reservation->setPrice($totalPrice);
        $reservation->setPassengersCount($passengersCount);

        // Créer une transaction en attente au lieu de transférer immédiatement les crédits
        $platformTransaction = new PlatformTransaction();
        $platformTransaction->setFromUser($freshUser); // passenger
        $platformTransaction->setToUser($carshare->getDriver()); // driver
        $platformTransaction->setAmount($totalPrice);
        $platformTransaction->setReservation($reservation);
        $platformTransaction->setDescription(sprintf(
            'Covoiturage (%d passager%s): %s', 
            $passengersCount,
            $passengersCount > 1 ? 's' : '', 
            $carshare->getFormattedRoute()
        ));

        $this->entityManager->persist($reservation);
        $this->entityManager->persist($platformTransaction);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            'Réservation confirmée pour %d passager%s ! %.2f crédits seront transférés après validation du trajet. Places restantes: %d',
            $passengersCount,
            $passengersCount > 1 ? 's' : '',
            $totalPrice,
            $carshare->getAvailablePlaces()
        ));

        return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
    }

    #[Route('/reservations', name: 'app_reservations_index')]
    public function myReservations(ReservationRepository $reservationRepository): Response
    {
        $sessionUser = $this->getUser();

        if (!$sessionUser instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        // Détacher immédiatement l'utilisateur de session pour éviter toute corruption
        $this->entityManager->detach($sessionUser);

        // Récupérer les réservations avec eager loading pour éviter les requêtes supplémentaires
        $reservations = $reservationRepository->findByPassenger($sessionUser);

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/reservation/{id}/cancel', name: 'app_reservation_cancel', methods: ['POST', 'DELETE'])]
    public function cancel(Request $request, Reservation $reservation, PlatformTransactionRepository $platformTransactionRepository): Response
    {
        $sessionUser = $this->getUser();

        // Vérifier que l'utilisateur est bien le passager de cette réservation
        if (!$sessionUser instanceof \App\Entity\User || $reservation->getPassenger()->getId() !== $sessionUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez annuler que vos propres réservations.');
            return $this->redirectToRoute('app_reservations_index');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('cancel'.$reservation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_reservations_index');
        }

        $carshare = $reservation->getCarshare();
        $passengersCount = $reservation->getPassengersCount();

        // Vérifier si le trajet a déjà commencé
        if ($carshare->getTripStatus() && $carshare->getTripStatus() !== 'PENDING') {
            $this->addFlash('error', 'Impossible d\'annuler une réservation pour un trajet déjà commencé.');
            return $this->redirectToRoute('app_reservations_index');
        }

        // Rechercher et supprimer la transaction en attente
        $pendingTransaction = $platformTransactionRepository->findPendingByReservation($reservation);
        if ($pendingTransaction) {
            // Pour une annulation de réservation, on supprime complètement la transaction
            // car il n'y a pas besoin de garder l'historique d'une transaction qui n'a jamais eu lieu
            $this->entityManager->remove($pendingTransaction);
        }

        // Supprimer la réservation
        $this->entityManager->remove($reservation);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            'Réservation annulée pour %d passager%s. Places disponibles: %d',
            $passengersCount,
            $passengersCount > 1 ? 's' : '',
            $carshare->getAvailablePlaces()
        ));

        return $this->redirectToRoute('app_reservations_index');
    }
}
