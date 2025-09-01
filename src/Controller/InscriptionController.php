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
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class InscriptionController extends AbstractController
{
    #[Route('/event/detail/{id}', name: 'event_detail', requirements: ['id' => '\d+'])]
    public function detail(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $inscription = new Registration();
        $form = $this->createForm(InscriptionType::class, $inscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inscription->setEvent($event);
            $inscription->setParticipant($this->getUser());
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
        EntityManagerInterface $em,
        RegistrationRepository $registrationRepository,
        #[Autowire(service: 'state_machine.participation')] WorkflowInterface $workflow
    ): Response {
        $user = $this->getUser();

        // -----------Vérifie si l'événement est ouvert-------
        if ($event->getState() !== 'OPEN') {
            $this->addFlash('error', 'Impossible de s’inscrire à cet événement.');
            return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
        }

        //---------------- Vérifie si l'utilisateur a déjà une inscription---------
        $registration = $registrationRepository->findOneByEventAndUser($event->getId(), $user->getId());

        if ($registration) {
            //-------------------- Tentative de réactivation------
            foreach (['restore_from_cancel', 'restore_from_notify'] as $transition) {
                if ($workflow->can($registration, $transition)) {
                    $workflow->apply($registration, $transition);
                    $registration->setCancellationDate(null);
                    $em->flush();

                    $this->addFlash('success', 'Votre inscription a été réactivée !');
                    return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
                }
            }

            $this->addFlash('error', 'Vous êtes déjà inscrit à cet événement.');
            return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
        }

        //  ------------ On Vérifie la capacité max avant nouvelle inscription------
        if ($registrationRepository->countByEvent($event->getId()) >= $event->getMaxParticipant()) {
            $this->addFlash('error', 'Le nombre maximum de participants est déjà atteint.');
            return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
        }

        //--------- On cree Nouvelle inscription-----
        $registration = (new Registration())
            ->setEvent($event)
            ->setParticipant($user);

        $em->persist($registration);

        //---------- Déclenche l'event entered.REGISTERED pour envoyer le mail
        if ($workflow->can($registration, 'register')) {
            $workflow->apply($registration, 'register');
        }

        $em->flush();

        $this->addFlash('success', 'Vous êtes bien inscrit à cet événement !');
        return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
    }



    #[Route('/event/desistement/{id}', name: 'event_desistement')]
    public function cancel(
        Event $event,
        EntityManagerInterface $em,
        RegistrationRepository $registrationRepository,
        #[Autowire(service: 'state_machine.participation')] WorkflowInterface $participationWorkflow
    ): Response {
        $registration = $registrationRepository->findOneByEventAndUser($event->getId(), $this->getUser()->getId());
        if (!$registration) {
            $this->addFlash('error', 'Vous n’êtes pas inscrit à cet événement.');
            return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
        }

        if ($participationWorkflow->can($registration, 'cancel')) {
            $participationWorkflow->apply($registration, 'cancel');
            $registration->setCancellationDate(new \DateTime());
            $em->flush();
        }

        $this->addFlash('success', 'Votre désistement a été pris en compte.');
        return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
    }

    #[Route('/my_registrations', name: 'my_registrations')]
    public function myRegistrations(RegistrationRepository $registrationRepository): Response
    {
        $user = $this->getUser();
        // --------------------------Récupère toutes les inscriptions de l'utilisateur triées par date de début d'événement
        $registrations = $registrationRepository->findBy(
            ['participant' => $user],
            ['id' => 'DESC']
        );
        return $this->render('event/my_registrations.html.twig', [
            'registrations' => $registrations,
        ]);
    }

}
