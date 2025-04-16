<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{TextType, NumberType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use App\Entity\Stock;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;





class MahdiProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextType::class)
            ->add('price', NumberType::class)
            //->add('stock_id', NumberType::class)
            /*->add('stock_id', IntegerType::class, [
                'label' => 'stock_id',
                'required' => true,
            ])*/
            ->add('stock', EntityType::class, [
                'class' => Stock::class,
                'choice_label' => 'stock_id', // ou un autre champ à afficher
                'label' => 'Stock',
                'required' => true,
            ])
            
            ->add('category', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
