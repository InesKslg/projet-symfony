<?php

namespace App\Controller;

use App\Entity\ThemeRequest;
use App\Form\ThemeRequestType;
use App\Repository\ThemeRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ThemeRequestController extends AbstractController
{
    #[Route('/theme/request', name: 'app_theme_request')]
    public function request(Request $request, EntityManagerInterface $em): Response
    {
        $themeRequest = new ThemeRequest();

        $form = $this->createForm(ThemeRequestType::class, $themeRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $themeRequest->setRequestedBy($this->getUser());
            $themeRequest->setStatus('pending'); // 'pending' = en attente

            $em->persist($themeRequest);
            $em->flush();

            $this->addFlash('success', 'Votre demande a été envoyée !');
            return $this->redirectToRoute('app_home'); // ou la route souhaitée
        }

        return $this->render('theme_request/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Pour que l'utilisateur puisse voir ses demandes
    #[Route('/theme/my-requests', name: 'app_theme_my_requests')]
    public function myRequests(ThemeRequestRepository $repo): Response
    {
        $user = $this->getUser();
        $requests = $repo->findBy(['requestedBy' => $user]);

        return $this->render('theme_request/my_requests.html.twig', [
            'requests' => $requests,
        ]);
    }
}