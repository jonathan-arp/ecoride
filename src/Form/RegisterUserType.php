<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class RegisterUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('lastname', TextType::class, [
            'label' => 'Nom',
            'required'=>false,
                'constraints' => [new NotBlank(["message"=>"Le nom ne peut pas être vide."]), 
                new Length(['min' => 2, 'max' => 30,'minMessage' => 'Le nom doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',]) ],
                
             'attr' => ['placeholder' => 'Entrez votre nom']
        ])
        ->add('firstname', TextType::class, [
            'label' => 'Prénom',
            'required'=>false,
                'constraints' => [new NotBlank(["message"=>"Le prénom ne peut pas être vide."]), 
                new Length(['min' => 2, 'max' => 30,'minMessage' => 'Le prénom doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.',]) ],
                
            'attr' => ['placeholder' => 'Entrez votre prénom']
        ])
        ->add('surname', TextType::class, [
            'label' => 'Pseudo',
            'required'=>false,
                'constraints' => [new NotBlank(["message"=>"Le pseudo ne peut pas être vide."]), 
                new Length(['min' => 2, 'max' => 30,'minMessage' => 'Le pseudo doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le pseudo ne peut pas dépasser {{ limit }} caractères.',]) ],

            'attr' => ['placeholder' => 'Entrez votre pseudo']
        ])
        ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'constraints' => [
                    new NotBlank(["message" => "L'email ne peut pas être vide."]),
                    new \Symfony\Component\Validator\Constraints\Email([
                        'message' => 'L\'adresse email "{{ value }}" n\'est pas valide.',
                        'mode' => 'html5'  // Change to strict to enforce proper validation
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Saisir votre Email',
                    'pattern' => '[a-z0-9._%+\\-]+@[a-z0-9.\\-]+\\.[a-z]{2,}$'
                ]   
            ])
        ->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'required'=>false,
            'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            'first_options'  => [
                'label' => 'Mot de passe',
                'attr' => ['placeholder' => 'Entrez votre mot de passe (6 caractères minimum)',"id"=>"password"],
                'hash_property_path' => 'password'
            ],
            'second_options' => [
                'label' => 'Confirmer le mot de passe',
                'attr' => ['placeholder' => 'Confirmez votre mot de passe'],
            ],
            'invalid_message' => 'Les mots de passe ne correspondent pas',
            'mapped' => false,
        ])
        ->add('phone', TextType::class, [
            'label' => 'Téléphone',
            'required'=>false,
                'constraints' => [new NotBlank(["message"=>"Le téléphone ne peut pas être vide."]), 
                new Length(['min' => 2, 'max' => 30,'minMessage' => 'Le téléphone doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le téléphone ne peut pas dépasser {{ limit }} caractères.',]) ],

            'attr' => ['placeholder' => 'Entrez votre téléphone']
        ])
        ->add('address', TextType::class, [
            'label' => 'Adresse',
            'required'=>false,
                'constraints' => [new NotBlank(["message"=>"L'adresse ne peut pas être vide."]), 
                new Length(['min' => 2, 'max' => 255,'minMessage' => 'L\'adresse doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères.',]) ],

            'attr' => ['placeholder' => 'Entrez votre adresse']
        ])
        ->add('birthday', DateType::class,
            [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Sélectionnez votre date de naissance',
                    'class' => 'form-control'
                ]
                ]);
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints'=> [
                new UniqueEntity([
                    'entityClass' => User::class,
                    'fields' => ['email'],
                    'message' => 'Cette adresse email est déjà utilisée.'
                ])
            ],
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'register_user',
        ]);
    }
}