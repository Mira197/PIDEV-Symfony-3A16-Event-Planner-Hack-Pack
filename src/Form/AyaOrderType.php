<?php
// src/Form/AyaOrderType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Order;

class AyaOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('exact_address', TextareaType::class, [
                'label' => 'Delivery Address',
                'attr' => ['placeholder' => 'Enter exact delivery address'],
            ])
            ->add('event_date', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('payment_method', ChoiceType::class, [
                'label' => 'Payment Method',
                'choices' => [
                    'Credit Card' => 'Credit Card',
                    'Cash on Delivery' => 'Cash on Delivery',
                    'Stripe' => 'Stripe',
                ],
                'placeholder' => 'Select a method',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'attr' => ['id' => 'order_form']
        ]);
    }
}
