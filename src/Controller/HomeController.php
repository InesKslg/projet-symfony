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
        // Toutes les photos publiques
        $allPhotos = $photosRepository->findBy(['public' => true], ['date_added' => 'DESC']);

        // Récupère tous les thèmes
        $allThemes = $themesRepository->findAll();

        // Tri par date du dernier ajout d'une photo associée
        usort($allThemes, function($a, $b) {
            $lastPhotoA = $a->getPhotos()->last();
            $lastPhotoB = $b->getPhotos()->last();

            $dateA = $lastPhotoA ? $lastPhotoA->getDateAdded()->getTimestamp() : 0;
            $dateB = $lastPhotoB ? $lastPhotoB->getDateAdded()->getTimestamp() : 0;

            return $dateB <=> $dateA; // plus récent en premier
        });

        // Prends les 3 thèmes les plus récents
        $recentThemes = array_slice($allThemes, 0, 3);

        // Photos du premier thème (par défaut)
        $defaultPhotos = [];
        if (!empty($recentThemes)) {
            $firstTheme = $recentThemes[0];

            foreach ($firstTheme->getPhotos() as $photo) {
                if ($photo->isPublic()) { // ⚡️ filtrer uniquement les publiques
                    $defaultPhotos[] = [
                        'photo' => $photo,
                        'themeId' => $firstTheme->getId(),
                    ];
                }
            }
        }

        return $this->render('home/index.html.twig', [
            'recentThemes' => $recentThemes,
            'allPhotos' => $allPhotos,
            'defaultPhotos' => $defaultPhotos,
            'firstTheme' => $recentThemes[0] ?? null,
        ]);
    }
}
