<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Site;
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
            // --- Champs mappés à Event ---
            ->add('name', TextType::class, [
                'label' => "Nom de l'événement",
                'required' => true,
                'attr' => ['placeholder' => "Ex: Sortie randonnée"],
            ])
            ->add('startDateTime', DateTimeType::class, [
                'label'  => 'Date de début',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: 120'],
                'help' => 'En minutes (ex: 90 = 1h30)',
            ])
            ->add('registrationDeadline', DateTimeType::class, [
                'label'  => "Date limite d'inscription",
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('maxParticipant', IntegerType::class, [
                'label' => 'Nombre max. de participants',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: 20', 'min' => 1],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => 'Quelques détails utiles...'],
            ])
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'name',
                'label' => 'Campus ENI',
                'placeholder' => 'Choisissez un campus',
                'required' => false,
            ])

            // --- Champs NON mappés (adresse/GPS) ---
            ->add('place', TextType::class, [
                'label' => 'Lieu',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'autocomplete-place',
                    'placeholder' => 'Tapez un lieu ou sélectionnez dans la liste',
                ],
            ])
            ->add('street', TextType::class, [
                'label' => 'Rue',
                'mapped' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Rue'],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'mapped' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Code postal'],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'mapped' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Ville'],
            ])
            ->add('gpsLatitude', NumberType::class, [
                'label' => 'Latitude',
                'mapped' => false,
                'required' => false,
                'scale' => 8,
                'attr' => ['placeholder' => 'Ex: 47.218371', 'step' => '0.000001'],
            ])
            ->add('gpsLongitude', NumberType::class, [
                'label' => 'Longitude',
                'mapped' => false,
                'required' => false,
                'scale' => 8,
                'attr' => ['placeholder' => '-1.553621', 'step' => '0.000001'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
