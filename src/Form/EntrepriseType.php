<?php
namespace App\Form;

use App\Entity\Entreprise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nomEntreprise', TextType::class)
            ->add('siret', TextType::class)
            ->add('email', EmailType::class)
            ->add('dateCreation', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('logo', FileType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('formeJuridique', TextType::class)
            ->add('status', TextType::class)
            ->add('numeroRue', TextType::class)
            ->add('nomRue', TextType::class)
            ->add('complementAdresse', TextType::class, [
                'required' => false,
            ])
            ->add('codePostal', TextType::class)
            ->add('ville', TextType::class)
            ->add('pays', TextType::class)
            ->add('telephone', TextType::class)
            ->add('save', SubmitType::class, [
                'label' => 'Valider',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Entreprise::class,
        ]);
    }
}
