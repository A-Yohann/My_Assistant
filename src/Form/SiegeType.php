<?php
namespace App\Form;

use App\Entity\Siege;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class SiegeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nomSiege', TextType::class, [
                'label'    => 'Nom du siège',
                'required' => false,
            ])
            ->add('addresseSiege', TextType::class, [
                'label'    => 'Adresse du siège',
                'required' => false,
            ])
            ->add('dateCreation', DateType::class, [
                'label'    => 'Date de création',
                'widget'   => 'single_text',
                'required' => false,
            ])
            ->add('statuJuridique', CheckboxType::class, [
                'label'    => 'Statut juridique actif',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Siege::class,
        ]);
    }
}