<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setSearchFields(['firstname', 'lastname', 'email', 'phone'])
            ->setDefaultSort(['lastname' => 'ASC'])
            ->setPaginatorPageSize(20);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        // Ne montrer que les utilisateurs normaux (pas les employés)
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        
        return $queryBuilder
            ->andWhere('entity.roles NOT LIKE :adminRole')
            ->andWhere('entity.roles NOT LIKE :employeeRole')
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->setParameter('employeeRole', '%ROLE_EMPLOYEE%');
    }

    public function configureActions(Actions $actions): Actions
    {
        $suspendAction = Action::new('suspend', 'Suspendre', 'fas fa-ban')
            ->linkToCrudAction('suspendUser')
            ->setCssClass('btn btn-warning')
            ->displayIf(function (User $user) {
                return !in_array('ROLE_SUSPENDED', $user->getRoles());
            });

        $unsuspendAction = Action::new('unsuspend', 'Réactiver', 'fas fa-check-circle')
            ->linkToCrudAction('unsuspendUser')
            ->setCssClass('btn btn-success')
            ->displayIf(function (User $user) {
                return in_array('ROLE_SUSPENDED', $user->getRoles());
            });

        return $actions
            ->disable(Action::NEW)
            ->add(Crud::PAGE_INDEX, $suspendAction)
            ->add(Crud::PAGE_INDEX, $unsuspendAction)
            ->add(Crud::PAGE_DETAIL, $suspendAction)
            ->add(Crud::PAGE_DETAIL, $unsuspendAction);
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('lastname', 'Nom'),
            TextField::new('firstname', 'Prénom'),
            TextField::new('surname', 'Pseudo'),
            TextField::new('email', 'Email'),
            TextField::new('phone', 'Téléphone'),
            TextField::new('address', 'Adresse'),
            
            DateTimeField::new('birthday', 'Date de naissance')
                ->setFormTypeOptions([
                    'widget' => 'single_text',
                    'html5' => true,
                    'required' => false,
                ])
                ->setFormat('yyyy-MM-dd')
                ->hideOnIndex(),
            
            FormField::addPanel('Photo')->onlyOnForms(),
            TextField::new('photoFile', 'Télécharger Photo')
                ->setFormType(VichImageType::class)
                ->onlyOnForms(),
                
            ImageField::new('photo', 'Photo')
                ->setBasePath('/uploads/user_photos/')
                ->onlyOnIndex(),
            
            FormField::addPanel('Paramètres et rôles')->onlyOnForms(),
            AssociationField::new('parameters', 'Paramètres')
                ->onlyOnForms(),
            
            ChoiceField::new('roles', 'Rôles')
                ->setChoices([
                    'Utilisateur' => 'ROLE_USER',
                    'Suspendu' => 'ROLE_SUSPENDED'
                ])
                ->allowMultipleChoices()
                ->onlyOnForms(),

            BooleanField::new('suspended', 'Suspendu')
                ->hideOnForm()
                ->renderAsSwitch(false)
                ->setHelp('Utilisateur suspendu')
                ->formatValue(function ($value, User $user) {
                    return in_array('ROLE_SUSPENDED', $user->getRoles());
                }),
        ];
    }

    public function suspendUser(): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getContext()->getEntity()->getInstance();
        
        if (!in_array('ROLE_SUSPENDED', $user->getRoles())) {
            $roles = $user->getRoles();
            $roles[] = 'ROLE_SUSPENDED';
            $user->setRoles($roles);
            
            $this->container->get('doctrine')->getManager()->flush();
            
            $this->addFlash('success', sprintf('L\'utilisateur %s %s a été suspendu.', $user->getFirstname(), $user->getLastname()));
        }

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]));
    }

    public function unsuspendUser(): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getContext()->getEntity()->getInstance();
        
        $roles = array_filter($user->getRoles(), function($role) {
            return $role !== 'ROLE_SUSPENDED';
        });
        $user->setRoles(array_values($roles));
        
        $this->container->get('doctrine')->getManager()->flush();
        
        $this->addFlash('success', sprintf('L\'utilisateur %s %s a été réactivé.', $user->getFirstname(), $user->getLastname()));

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]));
    }

}
