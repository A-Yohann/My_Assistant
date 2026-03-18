<?php
namespace App\Controller\Admin;

use App\Entity\BonDeCommande;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class BonDeCommandeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BonDeCommande::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnIndex()->hideOnForm();
        yield TextField::new('numeroBon', 'Numéro');
        yield DateField::new('dateCreation', 'Date création');
        yield NumberField::new('montantHT', 'Montant HT');
        yield NumberField::new('montantTtc', 'Montant TTC');
        yield ChoiceField::new('etat', 'Statut')->setChoices([
            'En attente' => 'en_attente',
            'Payé'       => 'paye',
        ]);
        yield AssociationField::new('entreprise', 'Entreprise');
        yield AssociationField::new('devis', 'Devis source');
    }
}