<?php

namespace App\Form;

use App\Entity\Car;
use App\Entity\Carshare;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Bundle\SecurityBundle\Security;

class CarshareType extends AbstractType
{
    public function __construct(private Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();

        $builder
            ->add('start', DateTimeType::class, [
                'label' => 'Date et heure de départ',
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La date de départ ne peut pas être vide.']),
                    new GreaterThan([
                        'value' => 'now',
                        'message' => 'La date de départ doit être dans le futur.'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('end', DateTimeType::class, [
                'label' => 'Date et heure d\'arrivée',
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La date d\'arrivée ne peut pas être vide.']),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('start_location', TextType::class, [
                'label' => 'Lieu de départ',
                'constraints' => [
                    new NotBlank(['message' => 'Le lieu de départ ne peut pas être vide.']),
                ],
                'attr' => [
                    'placeholder' => 'Ex: Paris, Gare du Nord',
                    'class' => 'form-control'
                ]
            ])
            ->add('end_location', TextType::class, [
                'label' => 'Lieu d\'arrivée',
                'constraints' => [
                    new NotBlank(['message' => 'Le lieu d\'arrivée ne peut pas être vide.']),
                ],
                'attr' => [
                    'placeholder' => 'Ex: Lyon, Part-Dieu',
                    'class' => 'form-control'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut du trajet',
                'choices' => [
                    'Disponible' => 'available',
                    'Complet' => 'full',
                    'En cours' => 'in_progress',
                    'Terminé' => 'completed',
                    'Annulé' => 'cancelled',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le statut ne peut pas être vide.']),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('place', IntegerType::class, [
                'label' => 'Nombre de places disponibles',
                'constraints' => [
                    new PositiveOrZero(['message' => 'Le nombre de places doit être positif ou zéro.']),
                ],
                'attr' => [
                    'placeholder' => 'Ex: 3',
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 8
                ],
                'required' => false
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix par personne',
                'currency' => 'EUR',
                'constraints' => [
                    new NotBlank(['message' => 'Le prix ne peut pas être vide.']),
                    new PositiveOrZero(['message' => 'Le prix doit être positif.']),
                ],
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'form-control'
                ]
            ])
            ->add('car', EntityType::class, [
                'class' => Car::class,
                'choice_label' => function(Car $car) {
                    return $car->getBrand()->getContent() . ' ' . $car->getModel() . ' (' . $car->getMatriculation() . ')';
                },
                'label' => 'Véhicule',
                'placeholder' => 'Sélectionnez votre véhicule',
                'query_builder' => function($repository) use ($user) {
                    return $repository->createQueryBuilder('c')
                        ->where('c.user = :user')
                        ->setParameter('user', $user)
                        ->orderBy('c.model', 'ASC');
                },
                'constraints' => [
                    new NotBlank(['message' => 'Vous devez sélectionner un véhicule.']),
                ],
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Carshare::class,
        ]);
    }
}
