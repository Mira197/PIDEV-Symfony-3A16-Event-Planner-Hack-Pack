<?php

namespace App\Form;

use App\Entity\Publication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class PublicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'The title is mandatory.']),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'\-]+$/u',
                        'message' => 'The title can only contain letters, spaces and dashes.'
                    ]),
                ],
            ])
            ->add('description', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Description is required.']),
                ],
            ])
            
            ->add('username', TextType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'username',
                'constraints' => [
                    new NotBlank(['message' => 'Username is required.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Publication::class,
        ]);
    }
}
