<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class AuthFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('email', null, [
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'L\'email ne peut pas être vide.'
                ]),
                new Assert\Email([
                    'message' => 'L\'email "{{ value }}" n\'est pas valide.'
                ]),
                new Assert\Regex([
                    'pattern' => '/^[^@]+@[^@]+\.com$/',
                    'message' => 'L\'email doit contenir un "@" et se terminer par ".com".'
                ])
            ]
        ])
        ->add('password', PasswordType::class, [
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'Le mot de passe ne peut pas être vide.'
                ]),
                new Assert\Length([
                    'min' => 6,
                    'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.'
                ])
            ]
        ])
            ->add('submit', SubmitType::class, ['label' => 'Login']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => false, // ➤ DÉSACTIVE la validation globale
        ]);
    }
}
