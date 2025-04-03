<?php
// src/Form/AyaOrderType.php
namespace App\Controller;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AyaOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('exact_address', TextareaType::class, [
                'label' => 'Delivery Address',
                'attr' => ['placeholder' => 'Enter exact delivery address']
            ])
            ->add('event_date', DateTimeType::class, [
                'label' => 'Event Date',
                'widget' => 'single_text',
                'input' => 'datetime', // ← important
                'html5' => true,
                'attr' => ['placeholder' => 'Select event date']
            ])                       
            ->add('payment_method', ChoiceType::class, [
                'label' => 'Payment Method',
                'choices' => [
                    'Cash on Delivery' => 'cash',
                    'Credit Card' => 'card',
                    'Stripe' => 'stripe'
                ],
                'placeholder' => 'Choose a method'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
