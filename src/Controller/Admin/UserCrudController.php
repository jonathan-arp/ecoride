<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Vich\UploaderBundle\Form\Type\VichImageType;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
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
                    'Administrateur' => 'ROLE_ADMIN',
                    'Employé' => 'ROLE_EMPLOYEE',
                    'Utilisateur' => 'ROLE_USER',
                ])
                ->allowMultipleChoices()
                ->onlyOnForms(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW);
    }

}
