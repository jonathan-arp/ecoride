<?php

namespace App\Form;

use App\Entity\Brand;
use App\Entity\Car;
use App\Enum\EnergyType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('model', TextType::class, [
                'label' => 'Modèle',
                'constraints' => [
                    new NotBlank(['message' => 'Le modèle ne peut pas être vide.']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le modèle doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le modèle ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => ['placeholder' => 'Ex: Clio, 308, Golf...', 'class' => 'form-control']
            ])
            ->add('matriculation', TextType::class, [
                'label' => 'Plaque d\'immatriculation',
                'constraints' => [
                    new NotBlank(['message' => 'La plaque d\'immatriculation ne peut pas être vide.']),
                    new Length([
                        'min' => 6,
                        'max' => 10,
                        'minMessage' => 'La plaque doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'La plaque ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => ['placeholder' => 'Ex: AB-123-CD', 'class' => 'form-control']
            ])
            ->add('color', TextType::class, [
                'label' => 'Couleur',
                'constraints' => [
                    new NotBlank(['message' => 'La couleur ne peut pas être vide.']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'La couleur doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'La couleur ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => ['placeholder' => 'Ex: Rouge, Bleu, Blanc...', 'class' => 'form-control']
            ])
            ->add('energyType', EnumType::class, [
                'class' => EnergyType::class,
                'label' => 'Type d\'énergie',
                'choice_label' => fn(EnergyType $energyType) => $energyType->value,
                'placeholder' => 'Sélectionnez le type d\'énergie',
                'attr' => ['class' => 'form-control']
            ])
            ->add('date_first_matricule', DateType::class, [
                'label' => 'Date de première immatriculation',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('brand', EntityType::class, [
                'class' => Brand::class,
                'choice_label' => 'content',
                'label' => 'Marque',
                'placeholder' => 'Sélectionnez la marque',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
        ]);
    }
}
