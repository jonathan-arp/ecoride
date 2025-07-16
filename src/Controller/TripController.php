<?php

namespace App\Controller;

use App\Entity\Carshare;
use App\Entity\Reservation;
use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\PlatformTransactionRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TripController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/carshare/{id}/start-trip', name: 'app_trip_start', methods: ['POST'])]
    public function startTrip(Request $request, Carshare $carshare): Response
    {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User || $carshare->getDriver() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez démarrer que vos propres covoiturages.');
            return $this->redirectToRoute('app_carshare');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('start-trip'.$carshare->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_carshare');
        }

        if (!$carshare->canBeStarted()) {
            $this->addFlash('error', 'Ce covoiturage ne peut pas être démarré.');
            return $this->redirectToRoute('app_carshare');
        }

        $carshare->startTrip();
        $this->entityManager->persist($carshare);
        $this->entityManager->flush();

        $this->addFlash('success', 'Trajet démarré ! Vous pouvez maintenant indiquer votre arrivée à destination.');

        return $this->redirectToRoute('app_carshare');
    }

    #[Route('/carshare/{id}/arrive-trip', name: 'app_trip_arrive', methods: ['POST'])]
    public function arriveTrip(Request $request, Carshare $carshare): Response
    {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User || $carshare->getDriver() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez marquer l\'arrivée que pour vos propres covoiturages.');
            return $this->redirectToRoute('app_carshare');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('arrive-trip'.$carshare->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_carshare');
        }

        if ($carshare->getTripStatus() !== 'STARTED') {
            $this->addFlash('error', 'Ce covoiturage doit d\'abord être démarré.');
            return $this->redirectToRoute('app_carshare');
        }

        $carshare->arriveTrip();
        $this->entityManager->persist($carshare);
        $this->entityManager->flush();

        $this->addFlash('success', 'Arrivée confirmée ! En attente de validation par les passagers.');

        return $this->redirectToRoute('app_carshare');
    }

    #[Route('/reservation/{id}/review-trip', name: 'app_trip_review', methods: ['GET', 'POST'])]
    public function reviewTrip(
        Request $request, 
        Reservation $reservation,
        ReviewRepository $reviewRepository
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User || $reservation->getPassenger() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez évaluer que vos propres réservations.');
            return $this->redirectToRoute('app_reservations_index');
        }

        if (!$reservation->canBeValidated()) {
            $this->addFlash('error', 'Ce trajet ne peut pas encore être évalué.');
            return $this->redirectToRoute('app_reservations_index');
        }

        // Check if user has already reviewed this carshare
        if ($reviewRepository->hasPassengerReviewedCarshare($user, $reservation->getCarshare()->getId())) {
            $this->addFlash('error', 'Vous avez déjà évalué ce conducteur pour ce trajet.');
            return $this->redirectToRoute('app_reservations_index');
        }

        $review = new Review();
        $review->setDriver($reservation->getCarshare()->getDriver());
        $review->setPassenger($user);
        $review->setCarshare($reservation->getCarshare());
        $review->setReservation($reservation);

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save the review
            $this->entityManager->persist($review);

            // Validate the reservation
            $reservation->validateTrip();
            $this->entityManager->persist($reservation);

            // Check if all reservations are validated to complete the trip
            $carshare = $reservation->getCarshare();
            if ($carshare->isWaitingForValidation()) {
                $allReservationsValidated = true;
                foreach ($carshare->getReservations() as $res) {
                    if (!$res->isPassengerValidated()) {
                        $allReservationsValidated = false;
                        break;
                    }
                }

                if ($allReservationsValidated) {
                    // Process all pending transactions
                    $platformTransactionRepository = $this->entityManager->getRepository(\App\Entity\PlatformTransaction::class);
                    foreach ($carshare->getReservations() as $res) {
                        $pendingTransaction = $platformTransactionRepository->findPendingByReservation($res);
                        if ($pendingTransaction) {
                            $pendingTransaction->process();
                            $this->entityManager->persist($pendingTransaction);
                        }
                    }

                    // Complete the trip and deduct driver costs
                    $carshare->completeTrip();
                    $this->entityManager->persist($carshare);

                    $this->addFlash('success', 'Trajet validé et évalué ! Les crédits ont été transférés.');
                } else {
                    $this->addFlash('success', 'Trajet validé et évalué ! En attente de validation des autres passagers.');
                }
            }

            $this->entityManager->flush();
            return $this->redirectToRoute('app_reservations_index');
        }

        return $this->render('trip/review.html.twig', [
            'reservation' => $reservation,
            'carshare' => $reservation->getCarshare(),
            'driver' => $reservation->getCarshare()->getDriver(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reservation/{id}/validate-trip', name: 'app_trip_validate', methods: ['POST'])]
    public function validateTrip(
        Request $request, 
        Reservation $reservation, 
        PlatformTransactionRepository $platformTransactionRepository
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User || $reservation->getPassenger() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez valider que vos propres réservations.');
            return $this->redirectToRoute('app_reservations_index');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('validate-trip'.$reservation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_reservations_index');
        }

        if (!$reservation->canBeValidated()) {
            $this->addFlash('error', 'Ce trajet ne peut pas encore être validé.');
            return $this->redirectToRoute('app_reservations_index');
        }

        // Valider la réservation
        $reservation->validateTrip();
        $this->entityManager->persist($reservation);

        // Vérifier si toutes les réservations sont validées
        $carshare = $reservation->getCarshare();
        if ($carshare->isWaitingForValidation()) {
            // Traitement des transactions en attente pour ce covoiturage
            $allReservationsValidated = true;
            foreach ($carshare->getReservations() as $res) {
                if (!$res->isPassengerValidated()) {
                    $allReservationsValidated = false;
                    break;
                }
            }

            if ($allReservationsValidated) {
                // Toutes les réservations sont validées, traiter les transactions
                foreach ($carshare->getReservations() as $res) {
                    $pendingTransaction = $platformTransactionRepository->findPendingByReservation($res);
                    if ($pendingTransaction) {
                        $pendingTransaction->process();
                        $this->entityManager->persist($pendingTransaction);
                    }
                }

                // Marquer le covoiturage comme terminé et déduire les frais conducteur
                $carshare->completeTrip();
                $this->entityManager->persist($carshare);

                $this->addFlash('success', 'Trajet validé ! Les crédits ont été transférés et les frais de plateforme déduits (2 crédits).');
            } else {
                $this->addFlash('success', 'Trajet validé ! En attente de validation des autres passagers.');
            }
        }

        $this->entityManager->flush();

        return $this->redirectToRoute('app_reservations_index');
    }

    #[Route('/driver/{id}/reviews', name: 'app_driver_reviews')]
    public function driverReviews(
        \App\Entity\User $driver,
        ReviewRepository $reviewRepository
    ): Response {
        if (!$driver->canDrive()) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un conducteur.');
        }

        $reviews = $reviewRepository->findPublishedByDriver($driver);
        $averageRating = $reviewRepository->getAverageRatingForDriver($driver);
        $totalReviews = $reviewRepository->countPublishedByDriver($driver);

        return $this->render('driver/reviews.html.twig', [
            'driver' => $driver,
            'reviews' => $reviews,
            'averageRating' => $averageRating,
            'totalReviews' => $totalReviews,
        ]);
    }
}
