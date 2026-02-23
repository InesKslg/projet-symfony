<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Photos;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class AlbumController extends AbstractController
{
    #[Route('/album', name: 'app_album')]
    public function index(EntityManagerInterface $em): Response
    {
        // Récupérer tous les albums (pas de filtre user pour éviter l'erreur)
        $albums = $em->getRepository(Album::class)->findAll();

        return $this->render('album/index.html.twig', [
            'albums' => $albums,
        ]);
    }

    #[Route('/album/add', name: 'app_album_add')]
    public function addAlbum(Request $request, EntityManagerInterface $em): Response
    {
        // Créer un nouvel album
        $album = new Album();

        // Créer le formulaire pour l'album (catégorie)
        $albumForm = $this->createForm(AlbumType::class, $album);
        $albumForm->handleRequest($request);

        // Créer un formulaire pour ajouter une photo/vidéo
        $photo = new Photos();
        $photoForm = $this->createForm(PhotoType::class, $photo);
        $photoForm->handleRequest($request);

        // Si l'album est soumis et valide
        if ($albumForm->isSubmitted() && $albumForm->isValid()) {
            $em->persist($album);
            $em->flush();

            $this->addFlash('success', 'Album créé !');
            return $this->redirectToRoute('app_album');
        }

        // Si la photo/vidéo est soumise et valide
        if ($photoForm->isSubmitted() && $photoForm->isValid()) {
            // Lier la photo/vidéo à l'album sélectionné
            $photo->setAlbum($album);
            $photo->setDateAdded(new \DateTimeImmutable());

            $em->persist($photo);
            $em->flush();

            $this->addFlash('success', 'Photo/vidéo ajoutée !');
            return $this->redirectToRoute('app_album');
        }

        // Afficher la page avec formulaires et albums existants
        $albums = $em->getRepository(Album::class)->findAll();

        return $this->render('album/index.html.twig', [
            'albums' => $albums,
            'albumForm' => $albumForm->createView(),
            'photoForm' => $photoForm->createView(),
        ]);
    }
}
