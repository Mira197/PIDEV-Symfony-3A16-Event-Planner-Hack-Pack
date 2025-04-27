<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;


class InscriptionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastName')
            ->add('firstName')
            ->add('username')
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe', // Label du champ
                
            ])
            // Champ de confirmation du mot de passe
            ->add('passwordConfirmation', PasswordType::class, [
                'mapped' => false, // Ne pas mapper ce champ à une propriété de l'entité
                'label' => 'Confirmation du mot de passe', // Label du champ
                
            ])
            ->add('email')
            ->add('numtel')
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Fournisseur' => 'FOURNISSEUR',
                    'Client' => 'CLIENT',
                ],
                'expanded' => true, // Afficher les radios boutons horizontalement
                'multiple' => false, 
                'data' => 'Fournisseur', // Sélection par défaut
                // Ne permettre qu'un seul choix
            ])
            ->add('submit', SubmitType::class, ['label' => 'inscrire']);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}