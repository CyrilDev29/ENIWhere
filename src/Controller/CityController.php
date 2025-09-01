<?php

namespace App\Controller;

use App\Entity\City;
use App\Form\CityType;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;



#[Route('/cities', name: 'city_')]
final class CityController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CityRepository $repo): Response
    {
        return $this->render('city/index.html.twig', [
            'cities' => $repo->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/cities/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $city = new City();
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($city);
            $em->flush();

            $this->addFlash('success', 'Ville créée avec succès !');
            return $this->redirectToRoute('city_index');
        }

        return $this->render('city/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(City $city, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CityType::class, $city)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Ville mise à jour avec succès!');
            return $this->redirectToRoute('city_index');
        }

        return $this->render('city/edit.html.twig', [
            'form' => $form->createView(),
            'city' => $city,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(City $city, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_city_'.$city->getId(), $request->request->get('_token'))) {
            $em->remove($city);
            $em->flush();
            $this->addFlash('success', 'Ville supprimée avec succès!');
        }

        return $this->redirectToRoute('city_index');
    }
}
