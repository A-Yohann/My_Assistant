<?php
namespace App\Controller\Admin;

use App\Entity\Client;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class ClientCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Client::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('idClient')->hideOnForm();
        yield TextField::new('nom');
        yield TextField::new('prenom');
        yield EmailField::new('email');
        yield TextField::new('telephone');
        yield TextField::new('ville');
        yield DateField::new('dateCreation', 'Créé le')->hideOnForm();
        yield AssociationField::new('user', 'Utilisateur');
    }
}