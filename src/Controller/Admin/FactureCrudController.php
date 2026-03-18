<?php
namespace App\Controller\Admin;

use App\Entity\Facture;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class FactureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Facture::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('numeroFacture', 'Numéro');
        yield DateField::new('dateCreation', 'Date création');
        yield DateField::new('dateEcheance', 'Date échéance');
        yield NumberField::new('montantHT', 'Montant HT');
        yield NumberField::new('montantTtc', 'Montant TTC');
        yield ChoiceField::new('etat', 'Statut')->setChoices([
            'Impayée' => 'impayee',
            'Payée'   => 'payee',
        ]);
        yield AssociationField::new('entreprise', 'Entreprise');
        yield AssociationField::new('bonDeCommande', 'Bon de commande');
    }
}