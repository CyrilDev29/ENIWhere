<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Helper\UserRegistration;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;


class RegistrationController extends AbstractController
{

    private EmailVerifier $emailVerifier;
    private UserRegistration $userRegistration;

    public function __construct(EmailVerifier $emailVerifier, UserRegistration $userRegistration)
    {
        $this->emailVerifier = $emailVerifier;
        $this->userRegistration = $userRegistration;
    }

    #[Route('/admin/users', name: 'admin_user_list')]
    public function list(UserRepository $userRepository): Response
    {
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
                if ($user->getPhotoName()) {
                    $oldPhotoPath = $this->getParameter('kernel.project_dir').'/public/uploads/photos/'.$user->getPhotoName();
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }
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
            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
            return $this->redirectToRoute('admin_user_list');
        }
        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'is_admin' => true,
            'user' => $user,
        ]);
    }

    #[Route('admin/user/create', name: 'admin_user_create')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'is_registration' => true,
            'is_admin' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $photoFile = $form->get('photo')->getData();
            $photoDir = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
            $this->userRegistration->registerUser(
                $user,
                $plainPassword,
                $userPasswordHasher,
                $entityManager,
                $slugger,
                $photoDir,
                $photoFile
            );

            $this->addFlash('success', 'Votre compte a été créé. Vérifiez votre email pour activer votre compte.');

            return $this->redirectToRoute('admin_user_list');
        }
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }




    #[Route('/admin/users/delete/{id}', name: 'admin_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            if ($user->getPhotoName()) {
                $oldPhotoPath = $this->getParameter('kernel.project_dir').'/public/uploads/photos/'.$user->getPhotoName();
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide, suppression annulée.');
        }

        return $this->redirectToRoute('admin_user_list');
    }




}
