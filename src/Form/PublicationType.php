<?php
// src/Form/PublicationType.php
namespace App\Form;

use App\Entity\Publication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PublicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ "username" en lecture seule
            ->add('username', TextType::class, [
                'required' => true,
                'mapped' => false, // Ne pas mapper à l'entité Publication
                'data' => $options['user'] ? $options['user']->getUsername() : null, // Remplir automatiquement avec le username de l'utilisateur connecté
                'attr' => [
                    'class' => 'form-input',
                    'readonly' => 'readonly', // Rendre le champ en lecture seule
                ],
            ])
            ->add('title', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-input'],
            ])
            ->add('description', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-input'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Publication::class,
            'user' => null, // Passer l'utilisateur comme option
        ]);
    }
}
