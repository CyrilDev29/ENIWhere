<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Place;
use Doctrine\DBAL\Types\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('street', TextType::class, ['label' => 'Adresse'])
            ->add('gpsLatitude', NumberType::class, ['label' => 'Latitude', 'required' => false])
            ->add('gpsLongitude', NumberType::class, ['label' => 'Longitude', 'required' => false])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => 'id',
                'Placeholder' => '-- choisir une ville',
                'label' => 'Ville',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Place::class,
        ]);
    }
}
