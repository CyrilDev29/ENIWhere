<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Helper\NominatimService;
use App\Repository\EventRepository;
use App\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventController extends AbstractController
{
    #[Route('/events', name: 'event_index')]
    public function index(EventRepository $eventRepository): Response
    {

        $events = $eventRepository->findAll();

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/events/new', name: 'event_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        StateRepository $stateRepository,
        NominatimService $nominatimService,
    ): Response {
        $event = new Event();


        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $event->setOrganizer($this->getUser());


            $stateCreee = $stateRepository->findOneBy(['label' => 'Créée']);
            $event->setState($stateCreee);

            $event->setCreatedDate(new \DateTime());


            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Sortie créée avec succès.');

            return $this->redirectToRoute('event_index');
        }

        return $this->render('event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/places', name: 'places_search')]
    public function search(Request $request, NominatimService $nominatim): JsonResponse
    {
        $q = $request->query->get('q', '');
        if (strlen($q) < 2) {
            return new JsonResponse([]);
        }

        return new JsonResponse($nominatim->search($q));
    }

}
