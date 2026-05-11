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

        // Trie par date de la dernière photo associée (plus récent en premier)
        usort($allThemes, function($a, $b) {
            $lastA = $a->getPhotos()->last();
            $lastB = $b->getPhotos()->last();
            $dateA = $lastA ? $lastA->getDateAdded()->getTimestamp() : 0;
            $dateB = $lastB ? $lastB->getDateAdded()->getTimestamp() : 0;
            return $dateB <=> $dateA;
        });

        // 4 thèmes les plus récents pour les pills
        $recentThemes = array_slice($allThemes, 0, 4);

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
            'recentThemes'  => $recentThemes,
            'allThemesJson' => json_encode(array_map(fn($t) => ['id' => (string)$t->getId(), 'nom' => $t->getNom()], $allThemes)),
            'defaultPhotos' => $defaultPhotos,
        ]);
    }
}
