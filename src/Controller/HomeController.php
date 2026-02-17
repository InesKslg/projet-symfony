<?php

namespace App\Controller;

use App\Repository\PhotosRepository; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PhotosRepository $photosRepository): Response
    {
        // Récupère toutes les photos
        $photos = $photosRepository->findAll();

        // Rendu du template index.html.twig
        return $this->render('home/index.html.twig', [
            'photos' => $photos,
        ]);
    }
}
