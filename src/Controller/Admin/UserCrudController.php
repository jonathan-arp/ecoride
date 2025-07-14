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
            TextField::new('id')->setLabel('ID')->onlyOnIndex(),
            TextField::new('lastname')->setLabel('Nom'),
            TextField::new('firstname')->setLabel('Prénom'),
            TextField::new('surname')->setLabel('Pseudo'),
            TextField::new('email')->setLabel('Email'),
            TextField::new('phone')->setLabel('Téléphone'),
            TextField::new('address')->setLabel('Adresse'),
            DateTimeField::new('birthday')
                ->setFormTypeOptions([
                    'widget' => 'single_text',
                    'html5' => true,
                    'required' => false,
                ])
                ->setFormat('yyyy-MM-dd')
                ->setFormTypeOption('input', 'datetime_immutable')
                ->setLabel('Date de naissance'),
            
            FormField::addPanel('Gestion des images')->setIcon('fa fa-image'),
            TextField::new('photoFile', 'Télécharger Photo')
                ->setFormType(VichImageType::class)
                ->onlyOnForms()
                ->setColumns(6),
                
            ImageField::new('photo', 'Aperçu Photo')
                ->setBasePath('/uploads/users/')
                ->onlyOnIndex(),
            
            ChoiceField::new('roles')
                ->setLabel('Rôles')
                ->setChoices([
                    'Administrateur' => 'ROLE_ADMIN',
                    'Employé' => 'ROLE_EMPLOYEE',
                    'Utilisateur' => 'ROLE_USER',
                ])
                ->allowMultipleChoices()
                ->renderExpanded()
                ->hideOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW);
    }

}
