<?php

namespace App\Controller;

use App\Entity\Album;
use App\Form\AlbumType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\AlbumRepository;
use Symfony\Component\HttpFoundation\JsonResponse;


final class AlbumController extends AbstractController
{
    #[Route('/album', name: 'app_album')]
    public function index(EntityManagerInterface $em): Response
    {
        $albums = $em->getRepository(Album::class)->findAll();

        $albumForm = $this->createForm(AlbumType::class);

        return $this->render('album/index.html.twig', [
            'albums' => $albums,
            'albumForm' => $albumForm->createView(),
        ]);
    }

    #[Route('/album/add', name: 'app_album_add', methods:['POST'])]
    public function addAlbum(Request $request, EntityManagerInterface $em): Response
    {
        $album = new Album();
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Assignation de l'utilisateur connecté
            $album->setUser($this->getUser());

            $em->persist($album);
            $em->flush();

            $this->addFlash('success', 'Album créé !');
            return $this->redirectToRoute('app_album');
        }

        $albums = $em->getRepository(Album::class)->findAll();

        return $this->render('album/index.html.twig', [
            'albums' => $albums,
            'albumForm' => $form->createView(),
        ]);
    }

    
}