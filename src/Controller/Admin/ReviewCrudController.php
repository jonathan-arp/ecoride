<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use App\Entity\Carshare;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReviewCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Avis')
            ->setEntityLabelInPlural('Avis')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['driver.firstname', 'driver.lastname', 'passenger.firstname', 'passenger.lastname', 'comment'])
            ->setPaginatorPageSize(20);
    }

    public function configureActions(Actions $actions): Actions
    {
        $publishAction = Action::new('publish', 'Publier', 'fa fa-check')
            ->linkToCrudAction('publishReview')
            ->displayIf(fn (Review $review) => $review->getStatus() === 'EN_ATTENTE');

        $eliminateAction = Action::new('eliminate', 'Éliminer', 'fa fa-times')
            ->linkToCrudAction('eliminateReview')
            ->displayIf(fn (Review $review) => $review->getStatus() === 'EN_ATTENTE');

        $refundAction = Action::new('refund', 'Rembourser passager', 'fa fa-money-bill-wave')
            ->linkToCrudAction('refundPassenger')
            ->displayIf(fn (Review $review) => 
                $review->getCarshare()->getTripStatus() === 'COMPLETED' && 
                $review->getStatus() === 'EN_ATTENTE'
            );

        return $actions
            ->add(Crud::PAGE_INDEX, $publishAction)
            ->add(Crud::PAGE_INDEX, $eliminateAction)
            ->add(Crud::PAGE_INDEX, $refundAction)
            ->add(Crud::PAGE_DETAIL, $publishAction)
            ->add(Crud::PAGE_DETAIL, $eliminateAction)
            ->add(Crud::PAGE_DETAIL, $refundAction)
            ->disable(Action::NEW)
            ->disable(Action::EDIT)
            ->disable(Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            
            AssociationField::new('driver', 'Conducteur')
                ->formatValue(fn ($value) => $value ? $value->getFirstname() . ' ' . $value->getLastname() : '')
                ->setRequired(false),
            
            AssociationField::new('passenger', 'Passager')
                ->formatValue(fn ($value) => $value ? $value->getFirstname() . ' ' . $value->getLastname() : '')
                ->setRequired(false),
            
            AssociationField::new('carshare', 'Covoiturage')
                ->formatValue(fn ($value) => $value ? $value->getFormattedRoute() : '')
                ->setRequired(false),
            
            IntegerField::new('rating', 'Note')
                ->formatValue(fn ($value) => str_repeat('⭐', $value ?? 0)),
            
            TextareaField::new('comment', 'Commentaire')
                ->setMaxLength(200)
                ->renderAsHtml(false),
            
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En attente' => 'EN_ATTENTE',
                    'Publié' => 'PUBLIE',
                    'Éliminé' => 'ELIMINE',
                ])
                ->renderAsBadges([
                    'EN_ATTENTE' => 'warning',
                    'PUBLIE' => 'success',
                    'ELIMINE' => 'danger',
                ]),
            
            DateTimeField::new('createdAt', 'Créé le')
                ->setFormat('dd/MM/yyyy HH:mm'),
            
            DateTimeField::new('moderatedAt', 'Modéré le')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->hideOnIndex(),
            
            AssociationField::new('moderatedBy', 'Modéré par')
                ->formatValue(fn ($value) => $value ? $value->getFirstname() . ' ' . $value->getLastname() : '')
                ->hideOnIndex(),
        ];
    }

    public function publishReview(Request $request, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $entityId = $request->query->get('entityId');
        
        if (!$entityId) {
            $this->addFlash('error', 'Identifiant de l\'avis manquant.');
            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        $review = $this->entityManager->getRepository(Review::class)->find($entityId);
        $moderator = $this->getUser();

        if (!$review instanceof Review) {
            $this->addFlash('error', 'Avis introuvable.');
            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        if ($review->getStatus() === 'EN_ATTENTE') {
            $review->publish($moderator);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'avis a été publié avec succès.');
        } else {
            $this->addFlash('warning', 'Cet avis ne peut pas être publié.');
        }

        return $this->redirect($adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }

    public function eliminateReview(Request $request, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $entityId = $request->query->get('entityId');
        
        if (!$entityId) {
            $this->addFlash('error', 'Identifiant de l\'avis manquant.');
            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        $review = $this->entityManager->getRepository(Review::class)->find($entityId);
        $moderator = $this->getUser();

        if (!$review instanceof Review) {
            $this->addFlash('error', 'Avis introuvable.');
            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        if ($review->getStatus() === 'EN_ATTENTE') {
            $review->eliminate($moderator);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'avis a été éliminé.');
        } else {
            $this->addFlash('warning', 'Cet avis ne peut pas être éliminé.');
        }

        return $this->redirect($adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }

    public function refundPassenger(Request $request, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $entityId = $request->query->get('entityId');
        
        if (!$entityId) {
            $this->addFlash('error', 'Identifiant de l\'avis manquant.');
            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        $review = $this->entityManager->getRepository(Review::class)->find($entityId);
        $admin = $this->getUser();

        if (!$review instanceof Review) {
            $this->addFlash('error', 'Avis introuvable.');
            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        $carshare = $review->getCarshare();
        $reservation = $review->getReservation();
        $passenger = $review->getPassenger();

        if (!$carshare || !$reservation || !$passenger) {
            $this->addFlash('error', 'Données manquantes pour le remboursement.');
            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        // Vérifier que le trajet est terminé
        if ($carshare->getTripStatus() !== 'COMPLETED') {
            $this->addFlash('error', 'Le trajet doit être terminé pour effectuer un remboursement.');
            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        try {
            // Créer un crédit de remboursement pour le passager
            $refundAmount = $carshare->getPrice();
            $refundCredit = new \App\Entity\Credit();
            $refundCredit->setUser($passenger);
            $refundCredit->setAmount($refundAmount);
            $refundCredit->setDescription(sprintf(
                'Remboursement suite à problème - Trajet %s du %s',
                $carshare->getFormattedRoute(),
                $carshare->getStart()->format('d/m/Y')
            ));
            $refundCredit->setCreatedAt(new \DateTimeImmutable());
            $refundCredit->setCarshare($carshare);

            $this->entityManager->persist($refundCredit);

            // Marquer l'avis comme traité/éliminé
            $review->eliminate($admin);

            // Optionnel : Retirer les crédits du conducteur s'ils ont été accordés
            $this->removeDriverCreditsIfNecessary($carshare, $refundAmount);

            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'Remboursement effectué : %s crédits accordés à %s %s',
                $refundAmount,
                $passenger->getFirstname(),
                $passenger->getLastname()
            ));

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du remboursement : ' . $e->getMessage());
        }

        return $this->redirect($adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }

    private function removeDriverCreditsIfNecessary(\App\Entity\Carshare $carshare, float $refundAmount): void
    {
        $driver = $carshare->getDriver();
        $creditRepository = $this->entityManager->getRepository(\App\Entity\Credit::class);

        // Chercher les crédits du conducteur pour ce covoiturage
        $driverCredits = $creditRepository->findBy([
            'user' => $driver,
            'carshare' => $carshare
        ]);

        foreach ($driverCredits as $credit) {
            // Créer un crédit négatif pour annuler les gains du conducteur
            $debitCredit = new \App\Entity\Credit();
            $debitCredit->setUser($driver);
            $debitCredit->setAmount(-$credit->getAmount());
            $debitCredit->setDescription(sprintf(
                'Annulation crédit suite à problème - Trajet %s du %s',
                $carshare->getFormattedRoute(),
                $carshare->getStart()->format('d/m/Y')
            ));
            $debitCredit->setCreatedAt(new \DateTimeImmutable());
            $debitCredit->setCarshare($carshare);

            $this->entityManager->persist($debitCredit);
        }
    }
}
