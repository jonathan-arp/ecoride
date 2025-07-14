<?php

namespace App\Controller\Admin;

use App\Entity\Parameter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class ParameterCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Parameter::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setLabel('Nom'),
            TextField::new('value')->setLabel('Valeur'),
            TextField::new('icon')->setLabel('Icône')->setHelp('Ex: fa-smoking, fa-paw, fa-child'),
            AssociationField::new('users')->setLabel('Utilisateurs')->hideOnForm(),
        ];
    }
}
