<?php

namespace App\Form;

use App\Entity\CodePromo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AyaCodePromoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codePromo', TextType::class, [
                'label' => 'Promo Code',
                'attr' => ['class' => 'form-control'],
                'property_path' => 'code_promo' // 🛠️ Mapping vers l'attribut réel
            ])
            ->add('pourcentage', NumberType::class, [
                'label' => 'Discount (%)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateExpiration', DateType::class, [
                'label' => 'Expiration Date',
                'widget' => 'single_text',
                'input' => 'datetime',
                'attr' => ['class' => 'form-control'],
                'property_path' => 'date_expiration' // 🛠️ idem ici
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CodePromo::class,
            'csrf_protection' => false,
        ]);
    }
}
