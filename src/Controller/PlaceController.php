<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Place;
use App\Form\CityType;
use App\Repository\PlaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/places', name: 'place_')]
final class PlaceController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(PlaceRepository $repo): Response
    {
        return $this->render('place/index.html.twig', [
            'places' => $repo->findBy([], ['name' => 'ASC']),
        ]);
    }
    #[Route('places/new', name: 'new_place', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $place = new City();
        $form = $this->createForm(CityType::class, $place)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($place);
            $em->flush();
            $this->addFlash('success', 'Lieu créé.');
            return $this->redirectToRoute('place_index');
        }
        return $this->render('place/new.html.twig', ['form' => $form->createView()]);
    }
    #[Route('place/edit/{id}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Place $place, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CityType::class, $place)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Lieu mise à jour.');
            return $this->redirectToRoute('place_index');

        }
        return $this->render('place/edit.html.twig', [
            'form' => $form->createView(),
            'place' => $place,
        ]);
    }


    #[Route('place/delete/{id}', name: 'delete', methods: [ 'POST'])]
    public function delete(Place $place,Request $request, EntityManagerInterface $em): Response
    {

        if ($this->isCsrfTokenValid('delete_place'.$place->getId(), $request->request->get('_token'))) {
            $em->remove($place);
            $em->flush();
            $this->addFlash('success', 'Lieu supprimé.');
        }
        return $this->redirectToRoute('place_index');
    }
}
