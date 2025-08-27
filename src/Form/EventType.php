<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
            ->add('placeName', TextType::class, [
                'label' => 'Lieu',
                'mapped' => false,
                'attr' => ['placeholder' => 'EX: Atomic café'],
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
