<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Fonction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'constraints' => [
                    new NotBlank(["message" => "Le nom ne peut pas être vide."]),
                    new Length([
                        'min' => 2, 
                        'max' => 30,
                        'minMessage' => 'Le nom doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'constraints' => [
                    new NotBlank(["message" => "Le prénom ne peut pas être vide."]),
                    new Length([
                        'min' => 2, 
                        'max' => 30,
                        'minMessage' => 'Le prénom doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('surname', TextType::class, [
                'label' => 'Pseudo',
                'required' => true,
                'constraints' => [
                    new NotBlank(["message" => "Le pseudo ne peut pas être vide."]),
                    new Length([
                        'min' => 2, 
                        'max' => 30,
                        'minMessage' => 'Le pseudo doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le pseudo ne peut pas dépasser {{ limit }} caractères.',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'constraints' => [
                    new NotBlank(["message" => "L'email ne peut pas être vide."]),
                    new \Symfony\Component\Validator\Constraints\Email([
                        'message' => 'L\'adresse email "{{ value }}" n\'est pas valide.',
                        'mode' => 'html5'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => true,
                'constraints' => [
                    new NotBlank(["message" => "Le téléphone ne peut pas être vide."]),
                    new Length([
                        'min' => 2, 
                        'max' => 30,
                        'minMessage' => 'Le téléphone doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le téléphone ne peut pas dépasser {{ limit }} caractères.',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => true,
                'constraints' => [
                    new NotBlank(["message" => "L'adresse ne peut pas être vide."]),
                    new Length([
                        'min' => 2, 
                        'max' => 255,
                        'minMessage' => 'L\'adresse doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères.',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('birthday', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('fonction', EntityType::class, [
                'class' => Fonction::class,
                'choice_label' => 'name',
                'label' => 'Je suis',
                'placeholder' => 'Choisissez votre rôle',
                'required' => true,
                'constraints' => [
                    new NotBlank(["message" => "Veuillez sélectionner votre rôle."])
                ],
                'attr' => ['class' => 'form-control'],
                'help' => 'Votre rôle détermine si vous pouvez ajouter des voitures et créer des covoiturages'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'account_form',
        ]);
    }
}
