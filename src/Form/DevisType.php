<?php
namespace App\Form;

use App\Entity\Devis;
use App\Entity\Client;
use App\Entity\Entreprise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;

class DevisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('clientExistant', EntityType::class, [
                'class'         => Client::class,
                'choice_label'  => function(Client $client) {
                    return $client->getNom() . ' ' . $client->getPrenom() . ' — ' . $client->getEmail();
                },
                'label'         => 'Sélectionner un client existant',
                'mapped'        => false,
                'required'      => false,
                'placeholder'   => '-- Nouveau client --',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('c')
                        ->where('c.user = :user')
                        ->setParameter('user', $options['user'])
                        ->orderBy('c.nom', 'ASC');
                },
            ])
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
            ->add('entreprise', EntityType::class, [
                'class'         => Entreprise::class,
                'choice_label'  => 'nomEntreprise',
                'label'         => 'Entreprise',
                'placeholder'   => 'Sélectionnez votre entreprise',
                'required'      => true,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('e')
                        ->where('e.user = :user')
                        ->setParameter('user', $options['user']);
                },
            ])
            ->add('dateCreation', DateType::class, [
                'label'  => 'Date de création',
                'widget' => 'single_text',
            ])
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
                'label'    => 'Code postal',
                'mapped'   => false,
                'required' => false,
            ])
            ->add('clientVille', TextType::class, [
                'label'    => 'Ville',
                'mapped'   => false,
                'required' => false,
            ])
            ->add('clientPays', CountryType::class, [
                'label'             => 'Pays',
                'mapped'            => false,
                'required'          => false,
                'preferred_choices' => ['FR', 'BE', 'CH', 'CA'],
                'placeholder'       => 'Sélectionnez un pays',
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