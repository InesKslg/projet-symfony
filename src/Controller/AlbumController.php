<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Photos;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AlbumController extends AbstractController
{
    #[Route('/album', name: 'app_album')]
    public function index(): Response
    {
        return $this->render('album/index.html.twig', [
            'controller_name' => 'AlbumController',
        ]);
    }

    #[Route('/album/add', name: 'app_album_add')]
    public function addAlbum(EntityManagerInterface $em): Response
    {
        $album = new Album();
        $album->setCategorie('Vacances');

        // Créer les photos
        $photo1 = new Photos();
        $photo1->setPhotoUrl('plage.jpg');
        $photo1->setDescription('Plage en été');
        $photo1->setDateAdded(new \DateTimeImmutable());
        $photo1->setPublic(true);

        $photo2 = new Photos();
        $photo2->setPhotoUrl('montagne.jpg');
        $photo2->setDescription('Montagne en hiver');
        $photo2->setDateAdded(new \DateTimeImmutable());
        $photo2->setPublic(true);

        // Ajouter les photos à l'album
        $album->addPhoto($photo1);
        $album->addPhoto($photo2);

        // Sauvegarde en base
        $em->persist($album);
        $em->persist($photo1);
        $em->persist($photo2);
        $em->flush();

        $albums = $em->getRepository(Album::class)->findAll();

        return $this->render('album/index.html.twig', ['albums' => $albums,]);

    }

}


