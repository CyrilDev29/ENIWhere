<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;


class RegistrationController extends AbstractController

{
 // a renommer les fichiers ce controller sera AdminController

    #[Route('/admin/users', name: 'admin_user_list')]
    public function list(UserRepository $userRepository): Response
    {

        //$this->denyAccessUnlessGranted('ROLE_ADMIN');
        $users = $userRepository->findAll();

        return $this->render('registration/list.html.twig', [
            'users' => $users,
        ]);
    }



    #[Route('/admin/users/update/{id}', name: 'admin_user_edit', requirements: ['id' => '\d+'])]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'is_registration' => false,
            'is_admin' => true,
            'is_edit' => true,
        ]);

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            }
            if ($form->has('isActive')) {
                $user->setIsActive($form->get('isActive')->getData());
            }

            $photoFile = $form->get('photo')->getData();


            if ($photoFile) {
                // Supprime lancienne photo si elle existe
                if ($user->getPhotoName()) {
                    $oldPhotoPath = $this->getParameter('kernel.project_dir').'/public/uploads/photos/'.$user->getPhotoName();
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                // Crée un nom unique pour le nouveau fichier
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                // Déplace le fichier vers le dossier uploads/photos
                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/photos',
                        $newFilename
                    );

                    // Enregistre le nom du fichier dans l'entity User
                    $user->setPhotoName($newFilename);

                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');

            return $this->redirectToRoute('admin_user_list');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'is_admin' => true,
        ]);
    }


    #[Route('/admin/user/create', name: 'admin_user_create')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher,  EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {

       // $this->denyAccessUnlessGranted('ROLE_ADMIN'); A activer je desactive que pour les tests
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'is_registration'=>true,
            'is_admin'=>true,

        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setIsActive(true);
            $email = trim($form->get('email')->getData());
            $user->setEmail($email);

            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {

                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();


                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/photos',
                        $newFilename
                    );


                    $user->setPhotoName($newFilename);

                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                }
            }




            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'User created successfully.');
            // do anything else you need here, like send an email

            return $this->redirectToRoute('admin_user_list');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }


}
