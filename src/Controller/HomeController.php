<?php

namespace App\Controller;

use App\Repository\PhotosRepository; // <-- attention au nom exact
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PhotosRepository $photosRepository): Response
    {
        // Récupération des photos
        $photos = $photosRepository->findAll();

        return $this->render('home/index.html.twig', [
            'photos' => $photos
        ]);
    }
}
