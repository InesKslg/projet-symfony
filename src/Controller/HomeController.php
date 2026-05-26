<?php

namespace App\Controller;

use App\Repository\PhotosRepository;
use App\Repository\ThemesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ThemesRepository $themesRepository,
        PhotosRepository $photosRepository
    ): Response {

        $allThemes = $themesRepository->findAll();
        usort($allThemes, fn($a, $b) => $b->getPhotos()->count() <=> $a->getPhotos()->count());

        $topThemes = array_map(fn($t) => [
            'id'  => $t->getId(),
            'nom' => $t->getNom(),
        ], array_slice($allThemes, 0, 4));

        $otherThemes = array_map(fn($t) => [
            'id'  => $t->getId(),
            'nom' => $t->getNom(),
        ], array_slice($allThemes, 4));

        // Photos publiques à afficher dans la galerie
        $allPublicPhotos = $photosRepository->findBy(['public' => true], ['date_added' => 'DESC']);

        // Tableau pour Twig : photo + themeId (premier thème si existant)
        $defaultPhotos = [];
        foreach ($allPublicPhotos as $photo) {
            $themeId = $photo->getThemes()->first() ? $photo->getThemes()->first()->getId() : null;
            $defaultPhotos[] = [
                'photo' => $photo,
                'themeId' => $themeId,
            ];
        }

        return $this->render('home/index.html.twig', [
            'topThemes'     => $topThemes,
            'otherThemes'   => $otherThemes,
            'allThemesJson' => json_encode(array_map(fn($t) => ['id' => (string)$t->getId(), 'nom' => $t->getNom()], $allThemes)),
            'defaultPhotos' => $defaultPhotos,
        ]);
    }
}
