<?php

namespace App\Controller;

use App\Entity\Photos;
use App\Entity\Themes;
use App\Entity\Album;
use App\Form\AlbumType;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;


final class ProductController extends AbstractController
{
    #[Route('/welcome', name: 'app_welcome')]
    public function welcome(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Notifications non lues (les 4 plus récentes)
        $notifications = $em->getRepository(Notification::class)->findBy(
            ['recipient' => $user, 'isRead' => false],
            ['id' => 'DESC'],
            4
        );

        // Photos privées
        $privatePhotos = $em->getRepository(Photos::class)
            ->findBy(['userPhoto' => $user, 'public' => false], ['date_added' => 'DESC']);

        // Albums de l'utilisateur
        $albums = $em->getRepository(Album::class)->findBy([
            'user' => $user
        ]);

        // --- FORMULAIRE ALBUM ---
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

        // Thèmes
        $themes = $em->getRepository(Themes::class)->findAll();

        // Formulaire demande de thème
        $themeRequest = new ThemeRequest();
        $themeRequestForm = $this->createForm(ThemeRequestType::class, $themeRequest);
        $themeRequestForm->handleRequest($request);

        // Vérifier le nombre de demandes en attente
        $pendingCount = $em->getRepository(ThemeRequest::class)->count([
            'requestedBy' => $user,
            'status' => 'pending'
        ]);

        if ($pendingCount >= 4) {
            $this->addFlash('error', 'Vous avez déjà 4 demandes en attente. Veuillez attendre leur validation.');
            return $this->redirectToRoute('app_welcome');
        }

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
            'albums' => $albums,          
            'albumForm' => $albumForm->createView(),         
            'themes' => $themes,
            'themeRequestForm' => $themeRequestForm->createView(),
            'notifications' => $notifications,
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

        // Vérifier le nombre de demandes en attente
        $pendingCount = $em->getRepository(ThemeRequest::class)->count([
            'requestedBy' => $user,
            'status' => 'pending'
        ]);

        if ($pendingCount >= 4) {
            return $this->json(['error' => true, 'message' => 'Vous avez déjà 4 demandes en attente.']);
        }

        $themeRequest = new ThemeRequest();
        $themeRequest->setRequestedBy($user);
        $themeRequest->setStatus('pending');
        $themeRequest->setTitle($title);
        $themeRequest->setDescription($description);

        $em->persist($themeRequest);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Votre demande de thème a été envoyée !']);
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
        $photo->addAlbum($album); 

        
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
        $this->addFlash('success', 'Photo ajoutée à l’album !');

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

        if (!$notif) {
            return $this->json(null);
        }

        return $this->json([
        'id' => $notif->getId(),
        'message' => $notif->getMessage()
    ]);

    }

    #[Route('/album/{id}/edit', name: 'app_edit_album', methods:['POST'])]
    public function editAlbum(Request $request, EntityManagerInterface $em, Album $album): Response
    {
        $album->setCategorie($request->request->get('categorie'));
        $album->setStatus($request->request->get('status') ? true : false);

        $em->flush();
        $this->addFlash('success', 'Album modifié !');
        return $this->redirectToRoute('app_welcome');
    }

    #[Route('/album/{id}', name: 'app_view_album')]
    public function viewAlbum(Album $album): Response
    {
        // Récupère les photos liées à cet album
        $photos = $album->getPhotos(); 

        return $this->render('product/view_album.html.twig', [
            'album' => $album,
            'photos' => $photos,
        ]);
    }

    #[Route('/album/{id}/delete', name: 'app_delete_album', methods:['POST','DELETE'])]
    public function deleteAlbum(EntityManagerInterface $em, Album $album): Response
    {
        $em->remove($album);
        $em->flush();

        $this->addFlash('success', 'Album supprimé !');
        return $this->redirectToRoute('app_welcome');
    }

    #[Route('/album/{id}/add-photo', name: 'app_add_photo_to_album', methods:['POST'])]
    public function addPhotoToAlbum(Request $request, EntityManagerInterface $em, Album $album): Response
    {
        $file = $request->files->get('photo_file');
        $description = $request->request->get('description');
        $isPublic = $request->request->get('public') ? true : false;

        if (!$file) {
            $this->addFlash('error', 'Veuillez sélectionner un fichier.');
            return $this->redirectToRoute('app_welcome');
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
        $filename = uniqid() . '.' . $file->guessExtension();
        $file->move($uploadsDir, $filename);

        $photo = new Photos();
        $photo->setPhotoUrl($filename);
        $photo->setDescription($description);
        $photo->setPublic($isPublic);
        $photo->setDateAdded(new \DateTimeImmutable());
        $photo->setUserPhoto($this->getUser());


        $em->persist($photo);
        $em->flush();

        $this->addFlash('success', 'Photo ajoutée à l’album !');
        return $this->redirectToRoute('app_welcome');
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
            ];
        }

        return $this->json([
            'albumName' => $album->getCategorie(),
            'photos' => $photosArray
        ]);
    }

}
