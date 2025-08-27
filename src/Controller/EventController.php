<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Event;
use App\Entity\Place;
use App\Form\EventType;
use App\Helper\NominatimService;
use App\Repository\EventRepository;
use App\Repository\PlaceRepository;
use App\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventController extends AbstractController
{
    private NominatimService $nominatimService;

    public function __construct(NominatimService $nominatimService)
    {
        $this->nominatimService = $nominatimService;
    }
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
    ): Response {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setOrganizer($this->getUser());
            $stateCreee = $stateRepository->findOneBy(['label' => 'CREATED']); // au moment de creartion created simple
            $event->setState($stateCreee);
            $event->setCreatedDate(new \DateTime());

            // ----------------Récupérer les données du formulaire---------------------
            $placeName = (string) $form->get('place')->getData();
            $cityName  = (string) $form->get('city')->getData();
            $lat       = (float) $form->get('gpsLatitude')->getData();
            $lon       = (float) $form->get('gpsLongitude')->getData();
            $street = (string) $form->get('street')->getData();
            $postalCode = (string) $form->get('postalCode')->getData();


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
}
