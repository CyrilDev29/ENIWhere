<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Registration;
use App\Form\InscriptionType;
use App\Repository\RegistrationRepository;
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
            $inscription->setParticipant($this->getUser()); // important pour associer l’utilisateur connecté
            $em->persist($inscription);
            $em->flush();
        }

        return $this->render('event/detail.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/event/inscription/{id}', name: 'event_inscription')]
    public function new(
        Event $event,
        Request $request,
        EntityManagerInterface $em,
        RegistrationRepository $registrationRepository
    ): Response {
        $now = new \DateTime();

        // Vérifier que l'événement est ouvert et dans les délais
        if ($event->getState()?->getLabel() !== 'OPEN' || $event->getRegistrationDeadline() < $now) {
            $this->addFlash('error', 'Impossible de s’inscrire à cet événement.');
            return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
        }

        // Vérifier si l'événement est complet
        $count = $registrationRepository->countByEvent($event->getId());
        if ($count >= $event->getMaxParticipant()) {
            $this->addFlash('error', 'Le nombre maximum de participants est déjà atteint.');
            return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
        }

        // Vérifier si l'utilisateur est déjà inscrit
        $existing = $registrationRepository->findOneByEventAndUser($event->getId(), $this->getUser()->getId());
        if ($existing) {
            $this->addFlash('error', 'Vous êtes déjà inscrit à cet événement.');
            return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
        }

        // Créer une nouvelle inscription
        $inscription = new Registration();
        $inscription->setEvent($event);
        $inscription->setParticipant($this->getUser());

        $em->persist($inscription);
        $em->flush();

        $this->addFlash('success', 'Vous êtes bien inscrit à cet événement !');

        return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
    }
}
