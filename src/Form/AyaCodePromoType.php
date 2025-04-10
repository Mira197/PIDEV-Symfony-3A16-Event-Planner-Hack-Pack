<?php

namespace App\Form;

use App\Entity\CodePromo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AyaCodePromoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codePromo', TextType::class, [
                'label' => 'Promo Code'
            ])
            ->add('pourcentage', NumberType::class, [
                'label' => 'Discount (%)'
            ])
            ->add('dateExpiration', DateType::class, [
                'label' => 'Expiration Date',
                'widget' => 'single_text',
                'input' => 'datetime', // ou datetime si non immutable
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CodePromo::class,
        ]);
    }
}
