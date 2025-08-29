<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Event;
use App\Entity\Place;
use App\Entity\Registration;
use App\Form\EventType;
use App\Form\InscriptionType;
use App\Helper\EventStateService;
use App\Helper\NominatimService;
use App\Repository\EventRepository;
use App\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events')]
final class EventController extends AbstractController
{
    private NominatimService $nominatimService;

    public function __construct(NominatimService $nominatimService)
    {
        $this->nominatimService = $nominatimService;
    }

    #[Route('/events', name: 'event_index')]
    public function index(Request $request, EventRepository $eventRepository): Response
    {
        $form = $this->createForm(\App\Form\EventFilterType::class);
        $form->handleRequest($request);

        $filters = $form->getData() ?? [];

        $events = $eventRepository->searchByFilters($filters, $this->getUser());

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'filterForm' => $form->createView(),
        ]);
    }

    #[Route('/events/new', name: 'event_new')]
    public function new(
        Request                $request,
        EntityManagerInterface $em,
    ): Response
    {
        $event = new Event();
        $event->setState('CREATED');
        $event->setCreatedDate(new \DateTime());
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setOrganizer($this->getUser());
            // ----------------Récupérer les données du formulaire---------------------
            $placeName = (string)$form->get('place')->getData();
            $cityName = (string)$form->get('city')->getData();
            $lat = (float)$form->get('gpsLatitude')->getData();
            $lon = (float)$form->get('gpsLongitude')->getData();
            $street = (string)$form->get('street')->getData();
            $postalCode = (string)$form->get('postalCode')->getData();

            // Gestion de la ville
            $city = null;
            if ($cityName) {
                $city = $em->getRepository(City::class)->findOneBy([
                    'name' => $cityName,
                    'postalCode' => $postalCode,
                ]);
                if (!$city) {
                    $city = new City();
                    $city->setName($cityName);
                    $city->setPostalCode($postalCode);
                    $em->persist($city);
                }
            }

            $place = null;
            if ($placeName && $city) {
                $place = $em->getRepository(Place::class)->findOneBy([
                    'name' => $placeName,
                    'street' => $street,
                    'city' => $city,
                ]);
                if (!$place) {
                    $place = new Place();
                    $place->setName($placeName ?: 'Lieu inconnu');
                    $place->setStreet($street ?: 'Rue inconnue');
                    $place->setGpsLatitude($lat);
                    $place->setGpsLongitude($lon);
                    $place->setCity($city);
                    $em->persist($place);
                }
            }
            $event->setPlace($place);
            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Sortie créée avec succès.');
            return $this->redirectToRoute('event_index');
        }

        return $this->render('event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/places/search', name: 'place_search')]
    public function placeSearch(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        if (!$query) {
            return new JsonResponse([]);
        }
        $results = $this->nominatimService->search($query);

        return new JsonResponse($results);
    }




    #[Route('/my_events', name: 'my_events')]
    public function myEvents(EventRepository $repo): Response
    {
        $user = $this->getUser();

        // Récupérer uniquement les événements dont l'utilisateur est l'organisateur
        $events = $repo->findBy(['organizer' => $user], ['startDateTime' => 'ASC']);

        return $this->render('event/my_events.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/manage/{id}', name: 'event_manage')]
    public function manage(Event $event): Response
    {

        if ($this->getUser() !== $event->getOrganizer()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas gérer cet événement.');
        }

        return $this->render('event/manage.html.twig', [
            'event' => $event,
        ]);
    }




    #[Route('/events/{id}/publish', name: 'event_publish', methods: ['POST'])]
    public function publish(Event $event, EventStateService $svc): Response
    {
        if ($svc->apply($event, 'publish')) {
            $this->addFlash('success', 'Événement publié ✅');
        } else {
            $this->addFlash('warning', "Impossible de publier depuis l'état {$event->getState()}.");
        }
        return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
    }

    #[Route('/events/{id}/open', name: 'event_open_regs', methods: ['POST'])]
    public function openRegs(Event $event, EventStateService $svc): Response
    {
        $ok = $svc->apply($event, 'open_regs');
        $this->addFlash($ok ? 'success' : 'warning', $ok ? 'Inscriptions ouvertes.' : "Transition impossible depuis l'état {$event->getState()}.");
        return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
    }

    #[Route('/events/{id}/close', name: 'event_close_regs', methods: ['POST'])]
    public function closeRegs(Event $event, EventStateService $svc): Response
    {
        $ok = $svc->apply($event, 'close_regs');
        $this->addFlash($ok ? 'success' : 'warning', $ok ? 'Inscriptions fermées.' : "Transition impossible depuis l'état {$event->getState()}.");
        return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
    }

    #[Route('/events/{id}/start', name: 'event_start', methods: ['POST'])]
    public function start(Event $event, EventStateService $svc): Response
    {
        $ok = $svc->apply($event, 'start');
        $this->addFlash($ok ? 'success' : 'warning', $ok ? 'Événement démarré.' : "Transition impossible depuis l'état {$event->getState()}.");
        return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
    }

    #[Route('/events/{id}/finish', name: 'event_finish', methods: ['POST'])]
    public function finish(Event $event, EventStateService $svc): Response
    {
        $ok = $svc->apply($event, 'finish');
        $this->addFlash($ok ? 'success' : 'warning', $ok ? 'Événement terminé.' : "Transition impossible depuis l'état {$event->getState()}.");
        return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
    }

    #[Route('/events/{id}/cancel', name: 'event_cancel', methods: ['POST'])]
    public function cancel(Event $event, EventStateService $svc): Response
    {
        $ok = $svc->apply($event, 'cancel');
        $this->addFlash($ok ? 'success' : 'warning', $ok ? 'Événement annulé.' : "Transition impossible depuis l'état {$event->getState()}.");
        return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
    }


}
