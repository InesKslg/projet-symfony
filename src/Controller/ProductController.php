<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Photos;
use App\Entity\Videos;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

final class ProductController extends AbstractController
{
    #[Route('/welcome', name: 'app_welcome')]
    public function welcome(EntityManagerInterface $em): Response
    {
        // Récupérer les albums de l'utilisateur connecté
        $albums = $em->getRepository(Album::class)->findBy(['user' => $this->getUser()]);

        return $this->render('product/index.html.twig', [
            'albums' => $albums,
        ]);
    }

    #[Route('/upload', name: 'app_upload', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $em): Response
    {
        $type = $request->request->get('type'); // photo ou video
        $description = $request->request->get('description');
        $isPublic = $request->request->has('public');
        $file = $request->files->get('file');

        if (!$file) {
            $this->addFlash('error', 'Veuillez sélectionner un fichier.');
            return $this->redirectToRoute('app_welcome');
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        $filename = uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($uploadsDir, $filename);
        } catch (FileException $e) {
            $this->addFlash('error', 'Erreur lors de l\'upload.');
            return $this->redirectToRoute('app_welcome');
        }

        // Vérifier si un album "Mes Médias" existe, sinon le créer
        $albumRepo = $em->getRepository(Album::class);
        $album = $albumRepo->findOneBy(['user' => $this->getUser(), 'categorie' => 'Mes Médias']);
        if (!$album) {
            $album = new Album();
            $album->setUser($this->getUser());
            $album->setCategorie('Mes Médias');
            $em->persist($album);
        }

        if ($type === 'photo') {
            $photo = new Photos();
            $photo->setPhotoUrl('/uploads/' . $filename);
            $photo->setDescription($description);
            $photo->setPublic($isPublic);
            $photo->setDateAdded(new \DateTimeImmutable());
            $album->addPhoto($photo);
            $em->persist($photo);
        } else {
            $video = new Videos();
            $video->setVideoUrl('/uploads/' . $filename);
            $video->setDescription($description);
            $video->setPublic($isPublic);
            $video->setDateAdded(new \DateTimeImmutable());
            $album->addVideo($video);
            $em->persist($video);
        }

        $em->persist($album);
        $em->flush();

        $this->addFlash('success', ucfirst($type) . ' ajoutée avec succès !');

        return $this->redirectToRoute('app_welcome');
    }
}
