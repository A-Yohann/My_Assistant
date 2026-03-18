<?php
namespace App\Controller\Admin;

use App\Entity\Note;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class NoteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Note::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('idNote')->hideOnForm();
        yield TextField::new('titre');
        yield TextareaField::new('contenu');
        yield BooleanField::new('priorite', 'Prioritaire');
        yield DateTimeField::new('dateCreation', 'Créée le')->hideOnForm();
        yield AssociationField::new('entreprise', 'Entreprise');
    }
}