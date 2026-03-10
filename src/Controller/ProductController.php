<?php

namespace App\Controller;

use App\Entity\Photos;
use App\Entity\Themes;
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
        $user = $this->getUser();

        // Récupérer toutes les photos privées de l'utilisateur
        $privatePhotos = $em->getRepository(Photos::class)
            ->findBy(['userPhoto' => $user, 'public' => false], ['date_added' => 'DESC']);

        // Récupérer tous les thèmes pour le <select> du modal
        $themes = $em->getRepository(Themes::class)->findAll();

        return $this->render('product/index.html.twig', [
            'photos' => $privatePhotos,
            'themes' => $themes,
        ]);
    }

    #[Route('/upload/photo', name: 'app_upload_photo', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $em): Response
    {
        $description = $request->request->get('description');
        $isPublic = $request->request->get('public') ? true : false;
        $file = $request->files->get('photo_file');

        if (!$file) {
            $this->addFlash('error', 'Veuillez sélectionner un fichier.');
            return $this->redirectToRoute('app_welcome');
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
        $filename = uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($uploadsDir, $filename);
        } catch (FileException $e) {
            $this->addFlash('error', 'Erreur lors de l\'upload.');
            return $this->redirectToRoute('app_welcome');
        }

        $photo = new Photos();
        $photo->setPhotoUrl($filename);
        $photo->setDescription($description);
        $photo->setPublic($isPublic);
        $photo->setDateAdded(new \DateTimeImmutable());
        $photo->setUserPhoto($this->getUser());

        // Ajouter le thème sélectionné
        $themeId = $request->request->get('theme_id');
        if ($themeId) {
            $theme = $em->getRepository(Themes::class)->find($themeId);
            if ($theme) {
                $photo->addTheme($theme);
            }
        }

        $em->persist($photo);
        $em->flush();

        $this->addFlash('success', 'Photo ajoutée avec succès !');
        return $this->redirectToRoute('app_welcome');
    }
}