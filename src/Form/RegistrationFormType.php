<?php

namespace App\Form;

use App\Entity\Site;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $isRegistration = $options['is_registration'];
        $isAdmin = $options['is_admin'];
        $isEdit = $options['is_edit']; // a utiliser pour acriver /desactiver user

        $builder
            ->add('lastName', TextType::class, [
                'label' => 'Last name',

            ])
            ->add('firstName',TextType::class, [
                'label' => 'First name',
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
            ])

            ->add('phoneNumber', TextType::class, [
                'label' => 'Phone number',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank([
                        'message' => "L'adresse email est obligatoire."
                    ]),
                    new Email([
                        'message' => "L'adresse email n'est pas valide."
                    ]),
                    new Regex([
                        'pattern' => "/^[^@]+@campus-eni\.fr$/",
                        'message' => "Seules les adresses @campus-eni.fr sont autorisées."
                    ])
                ]
            ]);

        $builder->add('plainPassword', PasswordType::class, [
            'mapped' => false,
            'required' => $isRegistration,
            'attr' => ['autocomplete' => 'new-password'],
            'constraints' => [
                new Length([
                    'min' => 6,
                    'minMessage' => 'The password must be at least {{ limit }} characters long',
                    'max' => 4096,
                ]),
                ...($isRegistration ? [new NotBlank(['message' => 'Please enter a password'])] : []),
            ],
        ]);

        if ($options['is_admin']) {
            $builder->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a site',
                'required' => false,
            ]);

            $builder->add('roles', ChoiceType::class, [
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
                'label' => 'Roles',
            ]);

        }

    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_registration' => false,
            'is_admin' => false,
            'is_edit' => false,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'event_item',
        ]);
    }
}
