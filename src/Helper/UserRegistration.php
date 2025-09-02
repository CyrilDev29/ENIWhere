<?php

namespace App\Helper;

use App\Entity\User;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserRegistration
{
    public function __construct(private EmailVerifier $emailVerifier) {}

    /**
     * Enregistre un utilisateur et envoie un email de confirmation
     *
     * @param User $user
     * @param string $plainPassword
     * @param UserPasswordHasherInterface $passwordHasher
     * @param EntityManagerInterface $entityManager
     * @param SluggerInterface $slugger
     * @param string|null $photoDir
     * @param UploadedFile|null $photoFile
     */
    public function registerUser(
        User $user,
        string $plainPassword,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ?string $photoDir = null,
        ?UploadedFile $photoFile = null
    ): void {


        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));


        $user->setIsActive(true);
        $user->setIsVerified(false);


        if ($photoFile && $photoDir) {
            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

            try {
                $photoFile->move($photoDir, $newFilename);
                $user->setPhotoName($newFilename);
            } catch (\Exception $e) {

            }
        }


        $entityManager->persist($user);
        $entityManager->flush();


        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('no-reply@eniwhere.com', 'Admin EniWhere'))
                ->to($user->getEmail())
                ->subject('Confirmez votre email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }
}
