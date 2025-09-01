<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class UserImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ pour sélectionner le fichier CSV
            ->add('csvFile', FileType::class, [
                'label' => 'Fichier CSV des utilisateurs',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    // On limite aux fichiers CSV uniquement
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'text/csv',
                            'text/plain',
                            'application/csv',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier CSV valide',
                    ])
                ],
                'attr' => [
                    'accept' => '.csv', // Permet de filtrer les fichiers dans l'explorateur
                ]
            ])

            ->add('submit', SubmitType::class, [
                'label' => 'Importer les utilisateurs',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }
}
