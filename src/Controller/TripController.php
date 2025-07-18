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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TripController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer
    ) {}

    #[Route('/carshare/{id}/start-trip', name: 'app_trip_start', methods: ['POST'])]
    public function startTrip(Request $request, Carshare $carshare): Response
    {
        $sessionUser = $this->getUser();

        if (!$sessionUser instanceof \App\Entity\User || $carshare->getDriver()->getId() !== $sessionUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez démarrer que vos propres covoiturages.');
            return $this->redirectToRoute('app_carshare_my');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('start-trip'.$carshare->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
        }

        if (!$carshare->canBeStarted()) {
            $this->addFlash('error', 'Ce covoiturage ne peut pas être démarré.');
            return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
        }

        $carshare->startTrip();
        $this->entityManager->persist($carshare);
        $this->entityManager->flush();

        $this->addFlash('success', 'Trajet démarré ! Vous pouvez maintenant indiquer votre arrivée à destination.');

        return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
    }

    #[Route('/carshare/{id}/arrive-trip', name: 'app_trip_arrive', methods: ['POST'])]
    public function arriveTrip(Request $request, Carshare $carshare): Response
    {
        $sessionUser = $this->getUser();

        if (!$sessionUser instanceof \App\Entity\User || $carshare->getDriver()->getId() !== $sessionUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez marquer l\'arrivée que pour vos propres covoiturages.');
            return $this->redirectToRoute('app_carshare_my');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('arrive-trip'.$carshare->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
        }

        if ($carshare->getTripStatus() !== 'STARTED') {
            $this->addFlash('error', 'Ce covoiturage doit d\'abord être démarré.');
            return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
        }

        $carshare->arriveTrip();
        $this->entityManager->persist($carshare);
        $this->entityManager->flush();

        // Envoyer un email aux participants pour leur demander de valider le trajet
        $this->sendTripValidationEmail($carshare);

        $this->addFlash('success', 'Arrivée confirmée ! En attente de validation par les passagers.');

        return $this->redirectToRoute('app_carshare_by_id', ['id' => $carshare->getId()]);
    }

    #[Route('/reservation/{id}/review-trip', name: 'app_trip_review', methods: ['GET', 'POST'])]
    public function reviewTrip(
        Request $request, 
        Reservation $reservation,
        ReviewRepository $reviewRepository
    ): Response {
        $sessionUser = $this->getUser();

        if (!$sessionUser instanceof \App\Entity\User || $reservation->getPassenger()->getId() !== $sessionUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez évaluer que vos propres réservations.');
            return $this->redirectToRoute('app_reservations_index');
        }

        // Utiliser une entité fraîche pour éviter la corruption de session
        $userId = $sessionUser->getId();
        $freshUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        
        if (!$freshUser) {
            return $this->redirectToRoute('app_login');
        }

        if (!$reservation->canBeValidated()) {
            $this->addFlash('error', 'Ce trajet ne peut pas encore être évalué.');
            return $this->redirectToRoute('app_reservations_index');
        }

        // Check if user has already reviewed this carshare
        if ($reviewRepository->hasPassengerReviewedCarshare($freshUser, $reservation->getCarshare()->getId())) {
            $this->addFlash('error', 'Vous avez déjà évalué ce conducteur pour ce trajet.');
            return $this->redirectToRoute('app_reservations_index');
        }

        $review = new Review();
        $review->setDriver($reservation->getCarshare()->getDriver());
        $review->setPassenger($freshUser);
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
                    // Process all pending transactions and persist created credits
                    $platformTransactionRepository = $this->entityManager->getRepository(\App\Entity\PlatformTransaction::class);
                    foreach ($carshare->getReservations() as $res) {
                        $pendingTransaction = $platformTransactionRepository->findPendingByReservation($res);
                        if ($pendingTransaction) {
                            $createdCredits = $pendingTransaction->process();
                            $this->entityManager->persist($pendingTransaction);
                            
                            // Persist the credits created during transaction processing
                            foreach ($createdCredits as $credit) {
                                $this->entityManager->persist($credit);
                            }
                        }
                    }

                    // Complete the trip and persist the driver cost credit
                    $driverCostCredit = $carshare->completeTrip();
                    $this->entityManager->persist($carshare);
                    $this->entityManager->persist($driverCostCredit);

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
        $sessionUser = $this->getUser();

        if (!$sessionUser instanceof \App\Entity\User || $reservation->getPassenger()->getId() !== $sessionUser->getId()) {
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

    private function sendTripValidationEmail(Carshare $carshare): void
    {
        $driver = $carshare->getDriver();
        $reservations = $carshare->getReservations();
        
        // Envoyer un email à chaque passager
        foreach ($reservations as $reservation) {
            $passenger = $reservation->getPassenger();
            if ($passenger && $passenger->getEmail()) {
                $this->sendValidationEmailToPassenger($passenger, $carshare, $driver, $reservation);
            }
        }
    }
    
    private function sendValidationEmailToPassenger(
        \App\Entity\User $passenger, 
        Carshare $carshare, 
        \App\Entity\User $driver, 
        Reservation $reservation
    ): void {
        // Vérifier si l'envoi d'emails est activé
        if (!$this->getParameter('app.enable_emails')) {
            error_log('Envoi d\'email désactivé en développement - Email de validation non envoyé');
            return;
        }

        $startDate = $carshare->getStart()->format('d/m/Y à H:i');
        $route = $carshare->getFormattedRoute();
        $price = number_format($carshare->getPrice(), 2, ',', ' ');
        
        $subject = sprintf('Validation requise - Trajet %s terminé', $route);
        
        $validationUrl = $this->generateUrl('app_trip_review', [
            'id' => $reservation->getId()
        ], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        
        $htmlContent = sprintf('
            <h2>Trajet terminé - Validation requise</h2>
            <p>Bonjour %s,</p>
            <p>Votre trajet avec %s %s est arrivé à destination :</p>
            <ul>
                <li><strong>Trajet :</strong> %s</li>
                <li><strong>Date :</strong> %s</li>
                <li><strong>Prix :</strong> %s €</li>
            </ul>
            
            <h3>📝 Action requise</h3>
            <p><strong>Veuillez vous rendre sur votre espace personnel pour valider que tout s\'est bien passé.</strong></p>
            <p>👉 <a href="%s" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Valider mon trajet</a></p>
            
            <h3>💰 Mise à jour des crédits</h3>
            <p>⚠️ <strong>Important :</strong> Les crédits du conducteur ne seront mis à jour qu\'après validation de tous les passagers.</p>
            
            <h3>⭐ Évaluation et avis</h3>
            <p>Vous pourrez également :</p>
            <ul>
                <li>Soumettre un avis sur le conducteur</li>
                <li>Attribuer une note (soumise à validation par un employé)</li>
            </ul>
            
            <h3>⚠️ Problème pendant le trajet ?</h3>
            <p>Si le trajet s\'est mal passé, vous pourrez :</p>
            <ul>
                <li>Indiquer que le trajet ne s\'est pas bien déroulé</li>
                <li>Ajouter un commentaire explicatif</li>
                <li>Un employé contactera le conducteur pour résoudre la situation</li>
                <li>Les crédits du conducteur seront suspendus en attendant la résolution</li>
            </ul>
            
            <p>Cordialement,<br>L\'équipe EcoRide</p>
        ', 
            $passenger->getFirstname(),
            $driver->getFirstname(), 
            $driver->getLastname(),
            $route,
            $startDate,
            $price,
            $validationUrl
        );
        
        $textContent = sprintf(
            "Trajet terminé - Validation requise\n\n" .
            "Bonjour %s,\n\n" .
            "Votre trajet avec %s %s est arrivé à destination :\n\n" .
            "Trajet : %s\n" .
            "Date : %s\n" .
            "Prix : %s €\n\n" .
            "ACTION REQUISE\n" .
            "Veuillez vous rendre sur votre espace personnel pour valider que tout s'est bien passé.\n" .
            "Lien de validation : %s\n\n" .
            "MISE À JOUR DES CRÉDITS\n" .
            "Important : Les crédits du conducteur ne seront mis à jour qu'après validation de tous les passagers.\n\n" .
            "ÉVALUATION ET AVIS\n" .
            "Vous pourrez également :\n" .
            "- Soumettre un avis sur le conducteur\n" .
            "- Attribuer une note (soumise à validation par un employé)\n\n" .
            "PROBLÈME PENDANT LE TRAJET ?\n" .
            "Si le trajet s'est mal passé, vous pourrez :\n" .
            "- Indiquer que le trajet ne s'est pas bien déroulé\n" .
            "- Ajouter un commentaire explicatif\n" .
            "- Un employé contactera le conducteur pour résoudre la situation\n" .
            "- Les crédits du conducteur seront suspendus en attendant la résolution\n\n" .
            "Cordialement,\n" .
            "L'équipe EcoRide",
            $passenger->getFirstname(),
            $driver->getFirstname(),
            $driver->getLastname(),
            $route,
            $startDate,
            $price,
            $validationUrl
        );
        
        $emailMessage = (new Email())
            ->from('noreply@ecoride.horizonduweb.fr')
            ->to($passenger->getEmail())
            ->subject($subject)
            ->text($textContent)
            ->html($htmlContent);
        
        try {
            $this->mailer->send($emailMessage);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas empêcher la validation
            error_log('Erreur envoi email validation trajet: ' . $e->getMessage());
        }
    }
}
