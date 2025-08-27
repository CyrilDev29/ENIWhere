<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Registration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InscriptionController extends AbstractController
{
    #[Route('/event/{id}/inscription', name: 'event_inscription')]
    public function new(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        // Vérifier que l'événement est ouvert et que la date limite n'est pas passée
        $now = new \DateTime();
        if ($event->getState()?->getLabel() !== 'Ouvert' || $event->getRegistrationDeadline() < $now) {
            $this->addFlash('error', 'Impossible de s’inscrire à cet événement.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        // Créer une nouvelle inscription
        $registration = new Registration();
        $registration->setEvent($event);
        $registration->setUser($this->getUser());

        // Sauvegarder en base
        $em->persist($registration);
        $em->flush();

        // Message flash pour feedback utilisateur
        $this->addFlash('success', 'Vous êtes bien inscrit à cet événement !');

        // Redirection vers la page de l'événement
        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }
}
