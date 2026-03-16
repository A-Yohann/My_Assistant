<?php
namespace App\Form;

use App\Entity\Devis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class DevisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('numeroDevis', TextType::class, [
                'label' => 'Numéro du devis',
            ])
            ->add('dateEmission', DateType::class, [
                'label' => 'Date d\'émission',
                'widget' => 'single_text',
            ])
            ->add('dateValidite', DateType::class, [
                'label' => 'Date de validité',
                'widget' => 'single_text',
            ])
            ->add('montantHT', NumberType::class, [
                'label' => 'Montant HT',
            ])
            ->add('montantTtc', NumberType::class, [
                'label' => 'Montant TTC',
            ])
            ->add('status', TextType::class, [
                'label' => 'Statut',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('entreprise', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'class' => \App\Entity\Entreprise::class,
                'choice_label' => 'nomEntreprise',
                'label' => 'Entreprise',
                'placeholder' => 'Sélectionnez votre entreprise',
                'required' => true,
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($options) {
                    $user = $options['user'];
                    return $er->createQueryBuilder('e')
                        ->where('e.user = :user')
                        ->setParameter('user', $user);
                },
            ])
            ->add('dateCreation', DateType::class, [
                'label' => 'Date de création',
                'widget' => 'single_text',
            ])
            ->add('signature', TextType::class, [
                'label' => 'Signature',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Devis::class,
            'user' => null,
        ]);
    }
}
