<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Profiler\Profile;

final class ProfileController extends AbstractController
{
    #[Route('/profile/edit', name: 'profile_edit')]
    public function edit(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'is_edit' => true,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour.');

            return $this->redirectToRoute('profile_show');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'is_admin' => false,
        ]);
    }

    #[Route('/profile', name: 'profile_show')]
    public function show(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('profile/show.html.twig', [
            'user' => $user,
        ]);
    }
    #[Route('/user/{id}', name: 'user_profile_show', requirements: ['id' => '\d+'])]
    public function showUser(User $user): Response
    {
        return $this->render('profile/show.html.twig', [
            'user' => $user,
        ]);
    }
}
