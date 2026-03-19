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
use Symfony\Component\Validator\Constraints\Regex;

class DevisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // --- Infos devis ---
            ->add('numeroDevis', TextType::class, [
                'label' => 'Numéro du devis',
            ])
            ->add('dateEmission', DateType::class, [
                'label'  => 'Date d\'émission',
                'widget' => 'single_text',
            ])
            ->add('dateValidite', DateType::class, [
                'label'  => 'Date de validité',
                'widget' => 'single_text',
            ])
            ->add('montantHT', NumberType::class, [
                'label' => 'Montant HT',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('entreprise', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'class'         => \App\Entity\Entreprise::class,
                'choice_label'  => 'nomEntreprise',
                'label'         => 'Entreprise',
                'placeholder'   => 'Sélectionnez votre entreprise',
                'required'      => true,
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($options) {
                    $user = $options['user'];
                    return $er->createQueryBuilder('e')
                        ->where('e.user = :user')
                        ->setParameter('user', $user);
                },
            ])
            ->add('dateCreation', DateType::class, [
                'label'  => 'Date de création',
                'widget' => 'single_text',
            ])

            // --- Infos client ---
            ->add('clientNom', TextType::class, [
                'label'    => 'Nom du client',
                'mapped'   => false,
                'required' => true,
            ])
            ->add('clientPrenom', TextType::class, [
                'label'    => 'Prénom du client',
                'mapped'   => false,
                'required' => true,
            ])
            ->add('clientEmail', TextType::class, [
                'label'    => 'Email du client',
                'mapped'   => false,
                'required' => true,
            ])
            ->add('clientTelephone', TextType::class, [
                'label'    => 'Téléphone du client',
                'mapped'   => false,
                'required' => true,
            ])
            ->add('clientNumeroRue', TextType::class, [
                'label'    => 'Numéro de rue',
                'mapped'   => false,
                'required' => false,
            ])
            ->add('clientNomRue', TextType::class, [
                'label'    => 'Nom de rue',
                'mapped'   => false,
                'required' => false,
            ])
            ->add('clientCodePostal', TextType::class, [
                'label' => 'Code postal',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                'Regex' => new Regex([
                    'pattern' => '/^\d{5}$/',
                'message' => 'Le code postal doit contenir exactement 5 chiffres.',
        ])
    ],
])
            ->add('clientVille', TextType::class, [
                'label'    => 'Ville',
                'mapped'   => false,
                'required' => false,
            ])
            ->add('clientPays', TextType::class, [
                'label'    => 'Pays',
                'mapped'   => false,
                'required' => false,
                'data'     => 'France',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Devis::class,
            'user'       => null,
        ]);
    }
}
