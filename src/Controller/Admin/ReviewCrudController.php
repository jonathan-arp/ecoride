<?php

namespace App\Controller\Admin;

use App\Entity\Review;
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

        return $actions
            ->add(Crud::PAGE_INDEX, $publishAction)
            ->add(Crud::PAGE_INDEX, $eliminateAction)
            ->add(Crud::PAGE_DETAIL, $publishAction)
            ->add(Crud::PAGE_DETAIL, $eliminateAction)
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
}
