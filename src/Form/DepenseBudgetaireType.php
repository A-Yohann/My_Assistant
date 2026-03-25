<?php
namespace App\Form;

use App\Entity\DepenseBudgetaire;
use App\Controller\DepenseBudgetaireController;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;


class DepenseBudgetaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categories = array_combine(
            array_keys(DepenseBudgetaireController::CATEGORIES),
            array_keys(DepenseBudgetaireController::CATEGORIES)
        );

        $builder
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
            ])
            ->add('montant', NumberType::class, [
                'label' => 'Montant unitaire (€)',
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité',
                'data'  => 1,
            ])
            ->add('dateDepense', DateType::class, [
                'label'  => 'Date',
                'widget' => 'single_text',
            ])
            ->add('categorie', ChoiceType::class, [
                'label'   => 'Catégorie',
                'choices' => $categories,
            ])
            ->add('moyenPaiement', CheckboxType::class, [
                'label'    => 'Payé par carte',
                'required' => false,
            ])
            ->add('justificatif', FileType::class, [
                'label'    => 'Justificatif / (PDF, image)',
                'required' => false,
                'mapped'   => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DepenseBudgetaire::class,
        ]);
    }
}