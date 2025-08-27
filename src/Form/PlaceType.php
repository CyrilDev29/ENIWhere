<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Place;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('name', TextType::class, [
        'label' => 'Nom du lieu',
        'attr' => [
            'list' => 'places-list',
            'data-autocomplete' => '/places/search', // route AJAX
        ]
    ])
        ->add('street', TextType::class, ['label' => 'Rue', 'required' => false])
        ->add('gpsLatitude', NumberType::class, ['required' => false])
        ->add('gpsLongitude', NumberType::class, ['required' => false])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => fn(City $c) => sprintf('%s (%s)', $c->getName(), $c->getPostalCode()),
                'placeholder' => 'Choisissez une ville',
                'attr' => [
                    'id' => 'place_city'
                ]
            ]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Place::class,
        ]);
    }
}
