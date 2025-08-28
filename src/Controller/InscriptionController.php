<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Registration;
use App\Form\InscriptionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class InscriptionController extends AbstractController
{

    #[Route('event/detail/{id}', name: 'event_detail', requirements: ['id' => '\d+'])]
    public function detail(Event $event, Request $request, EntityManagerInterface $em): Response

    {
        $inscription = new Registration();
        $form = $this->createForm(InscriptionType::class, $inscription);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $inscription->setEvent($event);
            $em->persist($inscription);
            $em->flush();
        }

        return $this->render('event/detail.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/event/inscription/{id}', name: 'event_inscription')]
    public function new(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        // Vérifier que l'événement est ouvert et que la date limite n'est pas passée
        $now = new \DateTime();
        if ($event->getState()?->getLabel() !== 'OPEN' || $event->getRegistrationDeadline() < $now) {
            $this->addFlash('error', 'Impossible de s’inscrire à cet événement.');
            return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
        }

        // Créer une nouvelle inscription
        $registration = new Registration();
        $registration->setEvent($event);
        $registration->setParticipant($this->getUser());

        // Sauvegarder en base
        $em->persist($registration);
        $em->flush();

        // Message flash pour feedback utilisateur
        $this->addFlash('success', 'Vous êtes bien inscrit à cet événement !');

        // Redirection vers la page de l'événement
        return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
    }



}
