<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Place;
use App\Entity\Site;
use App\Form\PlaceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => "Nom de l'événement",
            ])
            ->add('startDateTime', DateTimeType::class, [
                'label'  => 'Date de début',
                'widget' => 'single_text',
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée (minutes)',
            ])
            ->add('registrationDeadline', DateTimeType::class, [
                'label'  => "Date limite d'inscription",
                'widget' => 'single_text',
            ])
            ->add('maxParticipant', IntegerType::class, [
                'label' => 'Nombre max. de participants',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])

            ->add('place', TextType::class, [
                'label' => 'Lieu',
                'mapped' => false,
                'attr' => [
                    'class' => 'autocomplete-place',
                    'placeholder' => 'Tapez un lieu ou sélectionnez dans la liste'
                ]
            ])
            ->add('street', TextType::class, [
                'label' => 'Rue',
                'mapped' => false,
                'attr' => ['placeholder' => 'Saisissez la rue']
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'mapped' => false,
                'attr' => ['Saisissez le code postal']
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'mapped' => false,
                'attr' => ['Saisissez le code postal']
            ])
            ->add('gpsLatitude', NumberType::class, [
                'label' => 'Latitude',
                'mapped' => false,
                'attr' => ['Saisissez le code postal']
            ])
            ->add('gpsLongitude', NumberType::class, [
                'label' => 'Longitude',
                'mapped' => false,
                'attr' => ['Saisissez le code postal']
            ])
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'name',
                'label' => 'Campus ENI',
                'placeholder' => 'Choisissez un campus',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
