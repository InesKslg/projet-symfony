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

        // Récupère tous les thèmes
        $allThemes = $themesRepository->findAll();

        // Tri par date du dernier ajout d'une photo associée (plus récent en premier)
        usort($allThemes, function($a, $b) {
            $lastPhotoA = $a->getPhotos()->last();
            $lastPhotoB = $b->getPhotos()->last();
            $dateA = $lastPhotoA ? $lastPhotoA->getDateAdded()->getTimestamp() : 0;
            $dateB = $lastPhotoB ? $lastPhotoB->getDateAdded()->getTimestamp() : 0;
            return $dateB <=> $dateA;
        });

        // Prends les 3 thèmes les plus récents
        $recentThemes = array_slice($allThemes, 0, 3);

        // Photos publiques à afficher
        $allPublicPhotos = $photosRepository->findBy(['public' => true], ['date_added' => 'DESC']);

        // Tableau pour le Twig : photo + themeId (premier thème si existe)
        $defaultPhotos = [];
        foreach ($allPublicPhotos as $photo) {
            $themeId = $photo->getThemes()->first() ? $photo->getThemes()->first()->getId() : null;
            $defaultPhotos[] = [
                'photo' => $photo,
                'themeId' => $themeId,
            ];
        }

        return $this->render('home/index.html.twig', [
            'recentThemes' => $recentThemes,
            'defaultPhotos' => $defaultPhotos,
            'firstTheme' => $recentThemes[0] ?? null,
        ]);
    }
}
