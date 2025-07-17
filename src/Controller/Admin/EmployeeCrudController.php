<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class EmployeeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Employé')
            ->setEntityLabelInPlural('Employés')
            ->setSearchFields(['firstname', 'lastname', 'email', 'phone'])
            ->setDefaultSort(['lastname' => 'ASC'])
            ->setPaginatorPageSize(20);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        // Ne montrer que les employés (ROLE_EMPLOYEE)
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        
        return $queryBuilder
            ->andWhere('entity.roles LIKE :role')
            ->setParameter('role', '%ROLE_EMPLOYEE%');
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
            ->add(Crud::PAGE_INDEX, $suspendAction)
            ->add(Crud::PAGE_INDEX, $unsuspendAction)
            ->add(Crud::PAGE_DETAIL, $suspendAction)
            ->add(Crud::PAGE_DETAIL, $unsuspendAction);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('lastname', 'Nom'),
            TextField::new('firstname', 'Prénom'),
            TextField::new('surname', 'Surnom'),
            EmailField::new('email', 'Email'),
            TextField::new('phone', 'Téléphone'),
            TextField::new('address', 'Adresse'),
            DateField::new('birthday', 'Date de naissance'),
            AssociationField::new('fonction', 'Fonction'),
            ChoiceField::new('roles', 'Rôles')
                ->setChoices([
                    'Employé' => 'ROLE_EMPLOYEE',
                    'Administrateur' => 'ROLE_ADMIN',
                    'Suspendu' => 'ROLE_SUSPENDED'
                ])
                ->allowMultipleChoices()
                ->renderExpanded(false)
                ->renderAsBadges(),
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
            
            $this->addFlash('success', sprintf('L\'employé %s %s a été suspendu.', $user->getFirstname(), $user->getLastname()));
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
        
        $this->addFlash('success', sprintf('L\'employé %s %s a été réactivé.', $user->getFirstname(), $user->getLastname()));

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]));
    }
}
