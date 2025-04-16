<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Event;
use App\Entity\Location;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('start_date', null, [
                'widget' => 'single_text',
            ])
            ->add('end_date', null, [
                'widget' => 'single_text',
            ])
            ->add('locationName', TextType::class, [
                'mapped' => false, // Ne pas enregistrer ce champ en BDD
                'data' => $options['location_name'] ?? '', // récupéré depuis BookingController
                'disabled' => true,
                'label' => 'Location',
                'attr' => [
                    'readonly' => true,
                ]
            ])
            /*->add('event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => 'id',
            ])*/
            /*->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
            ])*/
            /*->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'id',
            ])*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'location_name' => null, // ✅ option personnalisée à transmettre depuis le controller
        ]);
    }
}
