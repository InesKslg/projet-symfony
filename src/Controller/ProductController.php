<?php

namespace App\Controller;

use App\Entity\Photos;
use App\Entity\Themes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\ThemeRequest;
use App\Form\ThemeRequestType;
use App\Entity\Notification;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelExif;

final class ProductController extends AbstractController
{
    #[Route('/welcome', name: 'app_welcome')]
    public function welcome(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // 🔔 Récupérer UNE SEULE notification non lue (la plus récente)
        $notifications = $em->getRepository(Notification::class)->findBy(
            ['recipient' => $user, 'isRead' => false],
            ['id' => 'DESC'],
            1
        );

        // Photos privées
        $privatePhotos = $em->getRepository(Photos::class)
            ->findBy(['userPhoto' => $user, 'public' => false], ['date_added' => 'DESC']);

        // Thèmes
        $themes = $em->getRepository(Themes::class)->findAll();

        // Formulaire demande de thème
        $themeRequest = new ThemeRequest();
        $themeRequestForm = $this->createForm(ThemeRequestType::class, $themeRequest);
        $themeRequestForm->handleRequest($request);

        if ($themeRequestForm->isSubmitted() && $themeRequestForm->isValid()) {
            $themeRequest->setRequestedBy($user);
            $themeRequest->setStatus('pending');

            $em->persist($themeRequest);
            $em->flush();

            $this->addFlash('success', 'Votre demande de thème a été envoyée !');
            return $this->redirectToRoute('app_welcome');
        }

        return $this->render('product/index.html.twig', [
            'photos' => $privatePhotos,
            'themes' => $themes,
            'themeRequestForm' => $themeRequestForm->createView(),
            'notifications' => $notifications,
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
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'upload.');
            return $this->redirectToRoute('app_welcome');
        }

        $photo = new Photos();
        $photo->setPhotoUrl($filename);
        $photo->setDescription($description);
        $photo->setPublic($isPublic);
        $photo->setDateAdded(new \DateTimeImmutable());
        $photo->setUserPhoto($this->getUser());

        
        try {
            $pel = new PelJpeg($uploadsDir.'/'.$filename);
            $exif = $pel->getExif();

            if ($exif instanceof PelExif) {
                $tiff = $exif->getTiff();
                $subIfd = $tiff->getSubIfd();

                $date = $subIfd->getDateTimeOriginal();
                $photo->setDatePrise($date ? new \DateTimeImmutable($date->format('Y-m-d H:i:s')) : new \DateTimeImmutable());

                $gps = $tiff->getGps();
                if ($gps) {
                    $lat = $gps->getLatitude();
                    $lon = $gps->getLongitude();
                    $photo->setLocalisation("{$lat}, {$lon}");
                } else {
                    $photo->setLocalisation('Non renseignée');
                }
            } else {
                $photo->setDatePrise(new \DateTimeImmutable());
                $photo->setLocalisation('Non renseignée');
            }
        } catch (\Exception $e) {
            $photo->setDatePrise(new \DateTimeImmutable());
            $photo->setLocalisation('Non renseignée');
        }

        // Thème
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

    #[Route('/photo/{id}/edit', name: 'app_edit_photo', methods:['POST'])]
    public function edit(Request $request, EntityManagerInterface $em, Photos $photo): Response
    {
        $photo->setDescription($request->request->get('description'));
        $photo->setPublic($request->request->get('public') ? true : false);

        $em->flush();
        $this->addFlash('success', 'Photo modifiée !');
        return $this->redirectToRoute('app_welcome');
    }

    #[Route('/photo/{id}/delete', name:'app_delete_photo', methods:['POST','DELETE'])]
    public function delete(EntityManagerInterface $em, Photos $photo): Response
    {
        foreach ($photo->getThemes() as $theme) {
            $photo->removeTheme($theme);
        }

        $em->flush();
        $em->remove($photo);
        $em->flush();

        $this->addFlash('success', 'Photo supprimée !');
        return $this->redirectToRoute('app_welcome');
    }

    #[Route('/notification/read/{id}', name: 'notification_read')]
    public function readNotification(Notification $notification, EntityManagerInterface $em): Response
    {
        $notification->setIsRead(true);
        $em->flush();

        return $this->redirectToRoute('app_welcome');
    }
}
