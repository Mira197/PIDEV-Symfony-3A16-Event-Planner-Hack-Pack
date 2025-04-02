<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('username', TextType::class, [
            'mapped' => false,
            'required' => true,
            'label' => 'Confirm your username',
            'constraints' => [
                new NotBlank(['message' => 'Please confirm your username.']),
            ],
            'attr' => [
                'placeholder' => 'Type your username to confirm',
            ],
        ])
        
            ->add('content', TextareaType::class, [
                'label' => 'Your comment',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'The comment cannot be empty.']),
                ],
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Write something...',
                    'class' => 'form-textarea'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
