<?php


namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


class AdminRegisterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('lastName', null, [
            'constraints' => [
                new Assert\NotBlank(['message' => 'Le nom ne peut pas être vide.']),
                new Assert\Length([
                    'min' => 2,
                    'max' => 50,
                    'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                    'maxMessage' => 'Le nom ne peut pas contenir plus de {{ limit }} caractères.',
                ]),
            ],
        ])
        
        ->add('firstName', null, [
            'constraints' => [
                new Assert\NotBlank(['message' => 'Le prénom ne peut pas être vide.']),
                new Assert\Length([
                    'min' => 2,
                    'max' => 50,
                    'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères.',
                    'maxMessage' => 'Le prénom ne peut pas contenir plus de {{ limit }} caractères.',
                ]),
            ],
        ])
        
        ->add('username', null, [
            'constraints' => [
                new Assert\NotBlank(['message' => 'Le nom d\'utilisateur ne peut pas être vide.']),
                new Assert\Length([
                    'min' => 4,
                    'max' => 30,
                    'minMessage' => 'Le nom d\'utilisateur doit contenir au moins {{ limit }} caractères.',
                    'maxMessage' => 'Le nom d\'utilisateur ne peut pas contenir plus de {{ limit }} caractères.',
                ]),
            ],
        ])
        ->add('email', null, [
            'constraints' => [
                new Assert\NotBlank(['message' => 'L\'email ne peut pas être vide.']),
                new Assert\Email(['message' => 'L\'email "{{ value }}" n\'est pas valide.']),
                new Assert\Regex([
                    'pattern' => '/^[^@]+@[^@]+\.[a-zA-Z]{2,6}$/',
                    'message' => 'L\'email doit contenir un "@" et un "." valide.',
                ]),
            ],
            'validation_groups' => ['admin_register'],
        ])
            ->add('numtel', null, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le numéro de téléphone ne peut pas être vide.']),
                    new Assert\Length([
                        'min' => 8,
                        'max' => 8,
                        'exactMessage' => "Le numéro de téléphone '{{ value }}' doit contenir exactement 8 chiffres."
                    ]),
                ],
                'validation_groups' => ['admin_register'],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le mot de passe ne peut pas être vide.']),
                    new Assert\Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
                'validation_groups' => ['admin_register'],
            ])
            ->add('passwordConfirmation', PasswordType::class, [
                'mapped' => false,
                'label' => 'Confirmation du mot de passe',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La confirmation du mot de passe ne peut pas être vide.']),
                ],
                'validation_groups' => ['admin_register'],
            ])
            ->add('role', HiddenType::class, [
                'data' => 'ADMIN',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['admin_register'], // Utilisation du groupe de validation
        ]);
    }
}
