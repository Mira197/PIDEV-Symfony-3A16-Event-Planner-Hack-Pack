<?php
// src/Form/AyaOrderType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Order;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
                    'Wallet Only' => 'Wallet Only', // 🔥 Ajout en interne
                ],
                'placeholder' => 'Select a method',
            ])
            ->add('final_total', HiddenType::class, [
                'mapped' => false, // très important : ce champ ne correspond pas directement à l'entité
            ]);
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                if (isset($data['final_total']) && (float)$data['final_total'] == 0.0) {
                    $data['payment_method'] = 'Wallet Only'; // ✅ Injecte Wallet Only si Total = 0
                    $event->setData($data);
                }
            });
    
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'attr' => ['id' => 'order_form']
        ]);
    }
}
