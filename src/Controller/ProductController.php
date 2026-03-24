<?php

namespace App\Controller;

use App\Entity\Photos;
use App\Entity\Themes;
use App\Entity\Album;
use App\Form\AlbumType;
use App\Entity\ThemeRequest;
use App\Form\ThemeRequestType;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelExif;

final class ProductController extends AbstractController
{
    #[Route('/welcome', name: 'app_welcome')]
    public function welcome(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $notifications = $em->getRepository(Notification::class)->findBy(
            ['recipient' => $user, 'isRead' => false],
            ['id' => 'DESC'],
            4
        );

        $privatePhotos = $em->getRepository(Photos::class)->findBy(
            ['userPhoto' => $user, 'public' => false],
            ['date_added' => 'DESC']
        );

        $albums = $em->getRepository(Album::class)->findBy(['user' => $user]);

        $album = new Album();
        $albumForm = $this->createForm(AlbumType::class, $album);
        $albumForm->handleRequest($request);
        if ($albumForm->isSubmitted() && $albumForm->isValid()) {
            $album->setUser($user);
            $em->persist($album);
            $em->flush();
            $this->addFlash('success', 'Album créé avec succès !');
            return $this->redirectToRoute('app_welcome'); 
        }

        $themes = $em->getRepository(Themes::class)->findAll();

        $themeRequest = new ThemeRequest();
        $themeRequestForm = $this->createForm(ThemeRequestType::class, $themeRequest);
        $themeRequestForm->handleRequest($request);

        $pendingCount = $em->getRepository(ThemeRequest::class)->count([
            'requestedBy' => $user,
            'status' => 'pending'
        ]);
        if ($pendingCount >= 4) {
            $this->addFlash('error', 'Vous avez déjà 4 demandes en attente.');
        }

        if ($themeRequestForm->isSubmitted() && $themeRequestForm->isValid() && $pendingCount < 4) {
            $themeRequest->setRequestedBy($user)->setStatus('pending');
            $em->persist($themeRequest);
            $em->flush();
            $this->addFlash('success', 'Votre demande de thème a été envoyée !');
            return $this->redirectToRoute('app_welcome');
        }

        return $this->render('product/index.html.twig', [
            'photos' => $privatePhotos,
            'albums' => $albums,
            'albumForm' => $albumForm->createView(),
            'themes' => $themes,
            'themeRequestForm' => $themeRequestForm->createView(),
            'notifications' => $notifications,
        ]);
    }

    #[Route('/upload/photo', name: 'app_upload_photo', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $file = $request->files->get('photo_file');
        if (!$file) {
            $this->addFlash('error', 'Veuillez sélectionner un fichier.');
            return $this->redirectToRoute('app_welcome');
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
        $filename = uniqid() . '.' . $file->guessExtension();
        $file->move($uploadsDir, $filename);

        $photo = new Photos();
        $photo->setPhotoUrl($filename)
              ->setDescription($request->request->get('description'))
              ->setPublic($request->request->has('public'))
              ->setDateAdded(new \DateTimeImmutable())
              ->setUserPhoto($user);

        $albumId = $request->request->get('album_id');
        if ($albumId) {
            $album = $em->getRepository(Album::class)->find($albumId);
            if ($album) $photo->addAlbum($album);
        }

        $themeId = $request->request->get('theme_id');
        if ($themeId) {
            $theme = $em->getRepository(Themes::class)->find($themeId);
            if ($theme) $photo->addTheme($theme);
        }

        try {
            $pel = new PelJpeg($uploadsDir.'/'.$filename);
            $exif = $pel->getExif();
            if ($exif instanceof PelExif) {
                $tiff = $exif->getTiff();
                $subIfd = $tiff->getSubIfd();
                $date = $subIfd->getDateTimeOriginal();
                $photo->setDatePrise($date ? new \DateTimeImmutable($date->format('Y-m-d H:i:s')) : new \DateTimeImmutable());
                $gps = $tiff->getGps();
                $photo->setLocalisation($gps ? $gps->getLatitude().', '.$gps->getLongitude() : 'Non renseignée');
            } else {
                $photo->setDatePrise(new \DateTimeImmutable())->setLocalisation('Non renseignée');
            }
        } catch (\Exception $e) {
            $photo->setDatePrise(new \DateTimeImmutable())->setLocalisation('Non renseignée');
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

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }

        $this->addFlash('success', 'Photo modifiée !');
        return $this->redirectToRoute('app_welcome');
    }

    #[Route('/photo/{id}/delete', name:'app_delete_photo', methods:['POST','DELETE'])]
    public function delete(EntityManagerInterface $em, Photos $photo): Response
    {
        foreach ($photo->getThemes() as $theme) $photo->removeTheme($theme);
        $em->remove($photo);
        $em->flush();

        $this->addFlash('success', 'Photo supprimée !');
        return $this->redirectToRoute('app_welcome');
    }

    #[Route('/album/{id}/delete', name:'app_delete_album', methods:['POST'])]
    public function deleteAlbum(EntityManagerInterface $em, Album $album): Response
    {
        foreach ($album->getPhotos() as $photo) {
            $photo->removeAlbum($album);
        }
        $em->remove($album);
        $em->flush();

        $this->addFlash('success', 'Album supprimé !');
        return $this->redirectToRoute('app_welcome');
    }

    #[Route('/album/{id}/add-photo', name:'app_add_photo_to_album', methods:['POST'])]
    public function addPhotoToAlbum(Request $request, EntityManagerInterface $em, Album $album): Response
    {
        $user = $this->getUser();

        // Récupère les IDs des photos sélectionnées correctement
        $photoIds = $request->request->all('photo_ids'); // <- retourne un tableau

        if (empty($photoIds)) {
            $this->addFlash('error', 'Veuillez sélectionner au moins une photo.');
            return $this->redirectToRoute('app_welcome');
        }

        foreach ($photoIds as $id) {
            $photo = $em->getRepository(Photos::class)->find($id);
            if ($photo) {
                $album->addPhoto($photo);
            }
        }

        $em->persist($album);
        $em->flush();

        $this->addFlash('success', 'Photos ajoutées à l’album !');
        return $this->redirectToRoute('app_welcome');
    }

    #[Route('/album/{id}/photos', name:'app_album_photos')]
    public function albumPhotos(Album $album): Response
    {
        return $this->render('album/_photos.html.twig', [
            'album' => $album,
            'photos' => $album->getPhotos(), // toutes les photos liées à l'album
        ]);
    }

    #[Route('/album/{id}/json', name: 'app_album_json', methods:['GET'])]
    public function albumJson(Album $album): JsonResponse
    {
        $photosArray = [];
        foreach ($album->getPhotos() as $photo) {
            $photosArray[] = [
                'id' => $photo->getId(),
                'photoUrl' => $photo->getPhotoUrl(),
                'description' => $photo->getDescription(),
                'public' => $photo->isPublic(),
                'theme' => $photo->getThemes()->first() ? $photo->getThemes()->first()->getNom() : null,
            ];
        }
        return $this->json([
            'albumName' => $album->getCategorie(),
            'photos' => $photosArray
        ]);
    }

    #[Route('/notification/read/{id}', name: 'notification_read')]
    public function readNotification(Notification $notification, EntityManagerInterface $em): Response
    {
        $notification->setIsRead(true);
        $em->flush();
        return $this->json(['status' => 'ok']);
    }

    #[Route('/notification/next', name: 'notification_next')]
    public function next(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $notif = $em->getRepository(Notification::class)->findOneBy(
            ['recipient' => $user, 'isRead' => false],
            ['id' => 'DESC']
        );
        if (!$notif) return $this->json(null);
        return $this->json([
            'id' => $notif->getId(),
            'message' => $notif->getMessage()
        ]);
    }

    #[Route('/theme/request/ajax', name: 'theme_request_ajax', methods:['POST'])]
    public function requestThemeAjax(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        if (!$title || !$description) {
            return $this->json(['error' => true, 'message' => 'Veuillez remplir tous les champs.']);
        }

        $pendingCount = $em->getRepository(ThemeRequest::class)->count([
            'requestedBy' => $user,
            'status' => 'pending'
        ]);
        if ($pendingCount >= 4) {
            return $this->json(['error' => true, 'message' => 'Vous avez déjà 4 demandes en attente.']);
        }

        $themeRequest = new ThemeRequest();
        $themeRequest->setRequestedBy($user)
                     ->setStatus('pending')
                     ->setTitle($title)
                     ->setDescription($description);

        $em->persist($themeRequest);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Votre demande de thème a été envoyée !']);
    }
}