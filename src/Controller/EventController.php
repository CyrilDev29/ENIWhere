<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Event;
use App\Entity\Place;
use App\Form\EventType;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/events')]
final class EventController extends AbstractController
{
    public function __construct(private NominatimService $nominatimService) {}

    // -------LISTE Event
    #[Route('', name: 'event_index')]
    public function index(Request $request, EventRepository $eventRepository): Response
    {
        $form = $this->createForm(\App\Form\EventFilterType::class);
        $form->handleRequest($request);
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $filters = $form->getData() ?? [];
        $events  = $eventRepository->searchByFilters($filters, $this->getUser(), $isAdmin);

        return $this->render('event/index.html.twig', [
            'events'     => $events,
            'filterForm' => $form->createView(),
        ]);
    }

    //--------- CRÉATION
    #[Route('/new', name: 'event_new')]
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
            return $this->redirectToRoute('my_events');
        }

        return $this->render('event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    // ----------AUTOCOMPLÉTION LIEUX-----API
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

    // ----------MES ÉVÉNEMENTS--------
    #[Route('/my_events', name: 'my_events')]
    public function myEvents(EventRepository $repo): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $events = $repo->findBy([], ['startDateTime' => 'ASC']);
        } else {
            $events = $repo->findBy(['organizer' => $user], ['startDateTime' => 'ASC']);
        }

        return $this->render('event/my_events.html.twig', ['events' => $events]);
    }

    // ----------PAGE DE GESTION-----
    #[Route('/manage/{id}', name: 'event_manage')]
    public function manage(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        //------ Vérifie si l'utilisateur peut éditer
        $canEdit = ($this->isGranted('ROLE_ADMIN') || $user === $event->getOrganizer()) && $event->isEditable();

        if (!$canEdit && $event->getState() === 'ONGOING') {
            $this->addFlash('warning', 'Cet événement est en cours et ne peut plus être modifié.');
        }
        $form = null;
        if ($canEdit) {
            $form = $this->createForm(EventType::class, $event);


            if (!$request->isMethod('POST')) {
                $place = $event->getPlace();
                $city  = $place?->getCity();

                $form->get('place')->setData($place?->getName());
                $form->get('street')->setData($place?->getStreet());
                $form->get('gpsLatitude')->setData($place?->getGpsLatitude());
                $form->get('gpsLongitude')->setData($place?->getGpsLongitude());

                $form->get('city')->setData($city?->getName());
                $form->get('postalCode')->setData($city?->getPostalCode());
            }

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Récupération des champs non mappés
                $placeName  = (string) $form->get('place')->getData();
                $street     = (string) $form->get('street')->getData();
                $lat        = (float)  $form->get('gpsLatitude')->getData();
                $lon        = (float)  $form->get('gpsLongitude')->getData();
                $cityName   = (string) $form->get('city')->getData();
                $postalCode = (string) $form->get('postalCode')->getData();

                // Gestion de la ville
                $city = $event->getPlace()?->getCity();
                if ($cityName) {
                    if (!$city || $city->getName() !== $cityName || $city->getPostalCode() !== $postalCode) {
                        $city = $em->getRepository(City::class)->findOneBy([
                            'name' => $cityName,
                            'postalCode' => $postalCode,
                        ]) ?? (new City())
                            ->setName($cityName)
                            ->setPostalCode($postalCode);
                        $em->persist($city);
                    }
                } else {
                    $city = null;
                }

                // Gestion du lieu
                $place = $event->getPlace();
                if ($placeName && $city) {
                    if (!$place || $place->getName() !== $placeName || $place->getStreet() !== $street || $place->getCity()?->getId() !== $city->getId()) {
                        $place = $em->getRepository(Place::class)->findOneBy([
                            'name' => $placeName,
                            'street' => $street,
                            'city' => $city,
                        ]) ?? (new Place())
                            ->setName($placeName ?: 'Lieu inconnu')
                            ->setStreet($street ?: 'Rue inconnue')
                            ->setCity($city);
                        $em->persist($place);
                    }
                    $place->setGpsLatitude($lat);
                    $place->setGpsLongitude($lon);
                } else {
                    $place = null;
                }

                $event->setPlace($place);

                $em->flush();
                $this->addFlash('success', 'Événement mis à jour');
                return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
            }
        }

        return $this->render('event/manage.html.twig', [
            'event' => $event,
            'form'  => $form?->createView(),
            'canEdit' => $canEdit,
        ]);
    }


    // ---------- TRANSITIONS WORKFLOW (POST) ----------

    #[Route('/{id}/publish', name: 'event_publish', methods: ['POST'])]
    public function publish(Event $event, EventStateService $svc): Response
    {
        if ($svc->apply($event, 'publish')) {
            $this->addFlash('success', 'Événement publié ');
        } else {
            $this->addFlash('warning', "Impossible de publier depuis « {$event->getState()} ».");
        }
        return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
    }

    #[Route('/{id}/open', name: 'event_open_regs', methods: ['POST'])]
    public function openRegs(Event $event, EventStateService $svc): Response
    {
        $ok = $svc->apply($event, 'open_regs');
        $this->addFlash($ok ? 'success' : 'warning', $ok ? 'Inscriptions ouvertes.' : "Transition impossible depuis « {$event->getState()} ».");
        return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
    }

    #[Route('/{id}/close', name: 'event_close_regs', methods: ['POST'])]
    public function closeRegs(Event $event, EventStateService $svc): Response
    {
        $ok = $svc->apply($event, 'close_regs');
        $this->addFlash($ok ? 'success' : 'warning', $ok ? 'Inscriptions fermées.' : "Transition impossible depuis « {$event->getState()} ».");
        return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
    }

    #[Route('/{id}/start', name: 'event_start', methods: ['POST'])]
    public function start(Event $event, EventStateService $svc): Response
    {
        $ok = $svc->apply($event, 'start');
        $this->addFlash($ok ? 'success' : 'warning', $ok ? 'Événement démarré.' : "Transition impossible depuis « {$event->getState()} ».");
        return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
    }

    #[Route('/{id}/finish', name: 'event_finish', methods: ['POST'])]
    public function finish(Event $event, EventStateService $svc): Response
    {
        $ok = $svc->apply($event, 'finish');
        $this->addFlash($ok ? 'success' : 'warning', $ok ? 'Événement terminé.' : "Transition impossible depuis « {$event->getState()} ».");
        return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
    }

    #[Route('/{id}/cancel', name: 'event_cancel', methods: ['POST'])]
    public function cancel(Event $event, EventStateService $svc,Request $request): Response
    {
        $reason = trim((string) $request->get('reason'));
        if($reason === ''){
            $this->addFlash('warning', 'Merci de saisir un motif d`annulation.');
            return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
        }

        $event->setCancellationReason($reason);

        $ok = $svc->apply($event, 'cancel');
        $this->addFlash($ok ? 'success' : 'warning', $ok ? 'Événement annulé.' : "Transition impossible depuis « {$event->getState()} ».");
        return $this->redirectToRoute('event_manage', ['id' => $event->getId()]);
    }


    #[Route('/{id}/delete', name: 'event_delete', methods: ['POST'])]
    public function delete(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet événement.');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete_event_'.$event->getId(), $request->request->get('_token'))) {
            $this->addFlash('warning', 'Token invalide.');
            return $this->redirectToRoute('my_events');
        }

        $em->remove($event);
        $em->flush();

        $this->addFlash('success', 'Événement supprimé avec succès.');
        return $this->redirectToRoute('my_events');
    }



}
