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
        $isEdit = $options['is_edit'];

        $builder
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('username', TextType::class, [
                'label' => 'Pseudo',
                'disabled' => $isEdit && !$isAdmin, // ----l'utilisateur ne peut pas changer son pseudo
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'disabled' => $isEdit && !$isAdmin, // ----l'utilisateur ne peut pas changer son email
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
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => $isRegistration,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                        'max' => 4096,
                    ]),
                    ...($isRegistration ? [new NotBlank(['message' => 'Veuillez entrer un mot de passe'])] : []),
                ],
            ]);


        if ($isAdmin) {

            $builder
                ->add('site', EntityType::class, [
                    'class' => Site::class,
                    'choice_label' => 'name',
                    'placeholder' => 'Choisir un site',
                    'required' => false,
                ])
                ->add('roles', ChoiceType::class, [
                    'choices' => [
                        'Utilisateur' => 'ROLE_USER',
                        'Administrateur' => 'ROLE_ADMIN',
                    ],
                    'expanded' => true,
                    'multiple' => true,
                    'label' => 'Rôles',
                ])
                ->add('isActive', ChoiceType::class, [
                    'choices' => [
                        'Actif' => true,
                        'Désactivé' => false,
                    ],
                    'expanded' => true,
                    'multiple' => false,
                    'label' => 'Statut',
                ]);

        } elseif ($isEdit) {

            $builder->add('site', TextType::class, [
                'label' => 'Site',
                'disabled' => true,
                'mapped' => false,
                'data' => $builder->getData()->getSite() ? $builder->getData()->getSite()->getName() : 'Non attribué',
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
            'csrf_token_id' => 'registration_item',
        ]);
    }
}
