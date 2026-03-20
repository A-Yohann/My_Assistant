<?php

namespace App\Form;
use App\Entity\Entreprise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;
class EntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nomEntreprise', TextType::class)
            ->add('siret', TextType::class,[
                'constraints' => [
                    new Regex([
                        'pattern' => '/^\d{14}$/',
                        'message' => 'Le SIRET doit contenir exactement 14 chiffres.',
                    ])
                ],
            ])
            ->add('email', EmailType::class)
            ->add('dateCreation', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('logo', FileType::class, [
                'required' => false,
                'mapped'   => false,
            ])
            ->add('formeJuridique', TextType::class)
            ->add('status', TextType::class)
            ->add('numeroRue', TextType::class)
            ->add('nomRue', TextType::class)
            ->add('complementAdresse', TextType::class, [
                'required' => false,
            ])
            ->add('codePostal', TextType::class, [
                'constraints' => [
                    new Regex([
                        'pattern' => '/^\d{5}$/',
                        'message' => 'Le code postal doit contenir exactement 5 chiffres.',
                    ])
                ],
            ])
            ->add('ville', TextType::class)
            ->add('pays', CountryType::class, [
                'label'             => 'Pays',
                'preferred_choices' => ['FR', 'BE', 'CH', 'CA'],
                'placeholder'       => 'Sélectionnez un pays',
            ])
            ->add('telephone', TextType::class)
;
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Entreprise::class,
        ]);
    }
}
