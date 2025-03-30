<?php

namespace App\Form;

use App\Entity\Location;
use App\Enum\City;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('address')
            ->add('city', EnumType::class, [
                'class' => City::class,
                'label' => 'City',
                'choice_label' => fn (City $city) => ucwords(strtolower(str_replace('_', ' ', $city->value))),
                'placeholder' => 'Select city',
            ])
            ->add('capacity')
            ->add('status', ChoiceType::class, [
                'choices'  => [
                    'Active' => 'Active',
                    'Inactive' => 'Inactive',
                    'Under Maintenance' => 'Under Maintenance',
                ],
                'placeholder' => 'Choose status',
                'label' => 'Status',
            ])
            
            ->add('description')
            ->add('dimension')
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Price per day',
                    'step' => '0.01', // permet les décimales
                    'min' => 0
                ]
            ])
            ->add('image', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Image',
            ])
            //->add('image_data')
            //->add('image_filename')
            ->add('has_3d_tour', CheckboxType::class, [
                'label' => 'Enable 3D Tour',
                'required' => false,
            ])
            ->add('table_set_count')
            ->add('include_corner_plants', CheckboxType::class, [
                'label' => 'Add Corner Plants',
                'required' => false,
            ])
            ->add('window_style', ChoiceType::class, [
                'choices' => [
                    'Modern' => 'Modern',
                    'Classic' => 'Classic',
                    'Double' => 'Double',
                    'Single' => 'Single',
                ],
                'label' => 'Window Style',
            ])
            ->add('door_style', ChoiceType::class, [
                'choices' => [
                    'Modern' => 'Modern',
                    'Classic' => 'Classic',
                    'Double' => 'Double',
                    'Single' => 'Single',
                ],
                'label' => 'Door Style',
            ])
            ->add('include_ceiling_lights', CheckboxType::class, [
                'label' => 'Add Ceiling Lights',
                'required' => false,
            ])
            ->add('light_color', ColorType::class, [
                'label' => 'Light Color',
                'required' => false,
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
        ]);
    }
}
