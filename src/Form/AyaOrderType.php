<?php
// src/Form/AyaOrderType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Order;

class AyaOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('exact_address', TextareaType::class, [
                'label' => 'Delivery Address',
                'attr' => ['placeholder' => 'Enter exact delivery address'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Delivery address is required.']),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'This value is too short. It should have {{ limit }} characters or more.'
                    ])
                ],
                'property_path' => 'exact_address'
            ])
            ->add('event_date', DateTimeType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select a valid event date.']),
                    new Assert\GreaterThan([
                        'value' => new \DateTime('+1 minute'),
                        'message' => 'The event date must be at least 1 minute in the future.'
                    ]),
                ]
            ])


            ->add('payment_method', ChoiceType::class, [
                'label' => 'Payment Method',
                'choices' => [
                    'Credit Card' => 'Credit Card',
                    'Cash on Delivery' => 'Cash on Delivery',
                    'Stripe' => 'Stripe',
                ],
                'placeholder' => 'Select a method',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select a payment method.'])
                ],
                'property_path' => 'payment_method'
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
    