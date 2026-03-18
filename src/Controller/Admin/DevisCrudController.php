<?php
namespace App\Controller\Admin;

use App\Entity\Devis;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class DevisCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Devis::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('numeroDevis', 'Numéro');
        yield DateField::new('dateEmission', 'Date émission');
        yield DateField::new('dateValidite', 'Date validité');
        yield NumberField::new('montantHT', 'Montant HT');
        yield NumberField::new('montantTtc', 'Montant TTC');
        yield ChoiceField::new('etat', 'Statut')->setChoices([
            'En attente' => 'en_attente',
            'Validé'     => 'valide',
            'Payé'       => 'paye',
        ]);
        yield AssociationField::new('entreprise', 'Entreprise');
        yield AssociationField::new('client', 'Client');
    }
}