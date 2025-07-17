<?php

namespace App\Form;

use App\Entity\Parameter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParameterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du paramètre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Musique, Animaux acceptés, Non-fumeur...'
                ]
            ])
            ->add('value', TextType::class, [
                'label' => 'Description/Valeur',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Description du paramètre'
                ]
            ])
            ->add('icon', TextType::class, [
                'label' => 'Icône (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: fas fa-music, fas fa-paw, fas fa-ban-smoking...'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Parameter::class,
        ]);
    }
}
