<?php

namespace App\Controller;

use App\Entity\Photos;
use App\Entity\Album;
use App\Entity\Themes;
use App\Entity\ThemeRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ApiProductController extends AbstractController
{
    // ─── Thèmes ─────────────────────────────────────────────────────────────────

    #[Route('/themes', name: 'api_themes_list', methods: ['GET'])]
    public function themes(EntityManagerInterface $em): JsonResponse
    {
        $themes = $em->getRepository(Themes::class)->findAll();
        return $this->json(array_map(fn($t) => [
            'id'  => $t->getId(),
            'nom' => $t->getNom(),
        ], $themes));
    }

    // ─── Photos ─────────────────────────────────────────────────────────────────

    #[Route('/photo/upload', name: 'api_photo_upload', methods: ['POST'])]
    public function uploadPhoto(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $file = $request->files->get('photo_file');

        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'Fichier invalide ou manquant'], Response::HTTP_BAD_REQUEST);
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
        $ext        = $file->getClientOriginalExtension() ?: ($file->guessExtension() ?? 'jpg');
        $filename   = uniqid() . '.' . $ext;

        try {
            $file->move($uploadsDir, $filename);
        } catch (FileException) {
            return $this->json(['error' => 'Erreur lors du téléversement'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $localisation = $request->request->get('localisation') ?: 'Non renseignée';

        // Priorité : date envoyée par le mobile, sinon EXIF, sinon maintenant
        $datePrise = new \DateTimeImmutable();
        $datePriseRaw = $request->request->get('date_prise');
        if ($datePriseRaw) {
            try { $datePrise = new \DateTimeImmutable($datePriseRaw); } catch (\Throwable) {}
        } else {
            try {
                $exif = @exif_read_data($uploadsDir . '/' . $filename);
                if ($exif) {
                    $raw = $exif['DateTimeOriginal'] ?? $exif['DateTime'] ?? null;
                    if ($raw) $datePrise = new \DateTimeImmutable($raw);
                }
            } catch (\Throwable) {}
        }

        $photo = new Photos();
        $photo->setPhotoUrl($filename)
              ->setDescription($request->request->get('description') ?? '')
              ->setPublic((bool) $request->request->get('public', false))
              ->setDateAdded(new \DateTimeImmutable())
              ->setDatePrise($datePrise)
              ->setLocalisation($localisation)
              ->setUserPhoto($user);

        $themeId = $request->request->get('theme_id');
        if ($themeId) {
            $theme = $em->getRepository(Themes::class)->find($themeId);
            if ($theme) $photo->addTheme($theme);
        }

        $em->persist($photo);
        $em->flush();

        return $this->json([
            'success'  => true,
            'photoUrl' => $photo->getPhotoUrl(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/photo/{id}', name: 'api_photo_delete', methods: ['DELETE'])]
    public function deletePhoto(Photos $photo, EntityManagerInterface $em): JsonResponse
    {
        if ($photo->getUserPhoto() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }
        foreach ($photo->getThemes() as $theme) $photo->removeTheme($theme);
        $em->remove($photo);
        $em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/photo/{id}/edit', name: 'api_photo_edit', methods: ['PATCH'])]
    public function editPhoto(Photos $photo, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($photo->getUserPhoto() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }
        $data = json_decode($request->getContent(), true) ?? [];
        if (array_key_exists('description', $data)) $photo->setDescription($data['description']);
        if (array_key_exists('public', $data))      $photo->setPublic((bool) $data['public']);
        $em->flush();
        return $this->json(['success' => true]);
    }

    // ─── Albums ─────────────────────────────────────────────────────────────────

    #[Route('/album/create', name: 'api_album_create', methods: ['POST'])]
    public function createAlbum(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];
        $categorie = trim($data['categorie'] ?? '');

        if ($categorie === '') {
            return $this->json(['error' => "Le nom de l'album est requis"], Response::HTTP_BAD_REQUEST);
        }

        $album = new Album();
        $album->setCategorie($categorie)->setUser($user);
        $em->persist($album);
        $em->flush();

        return $this->json([
            'success'   => true,
            'albumId'   => $album->getId(),
            'categorie' => $album->getCategorie(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/album/{id}', name: 'api_album_detail', methods: ['GET'])]
    public function albumDetail(Album $album): JsonResponse
    {
        if ($album->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }
        return $this->json([
            'id'        => $album->getId(),
            'categorie' => $album->getCategorie(),
            'photos'    => $album->getPhotos()->map(fn($p) => [
                'id'          => $p->getId(),
                'photoUrl'    => $p->getPhotoUrl(),
                'description' => $p->getDescription(),
                'themes'      => $p->getThemes()->map(fn($t) => $t->getNom())->toArray(),
            ])->toArray(),
        ]);
    }

    #[Route('/album/{id}/add-photos', name: 'api_album_add_photos', methods: ['POST'])]
    public function addPhotosToAlbum(Album $album, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if ($album->getUser() !== $user) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }
        $data     = json_decode($request->getContent(), true) ?? [];
        $photoIds = $data['photo_ids'] ?? [];
        foreach ($photoIds as $photoId) {
            $photo = $em->getRepository(Photos::class)->find($photoId);
            if ($photo && $photo->getUserPhoto() === $user) {
                $album->addPhoto($photo);
            }
        }
        $em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/album/{id}', name: 'api_album_delete', methods: ['DELETE'])]
    public function deleteAlbum(Album $album, EntityManagerInterface $em): JsonResponse
    {
        if ($album->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }
        foreach ($album->getPhotos() as $photo) $album->removePhoto($photo);
        $em->remove($album);
        $em->flush();
        return $this->json(['success' => true]);
    }

    // ─── Demande thème ──────────────────────────────────────────────────────────

    #[Route('/theme/request', name: 'api_theme_request_create', methods: ['POST'])]
    public function themeRequest(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $data        = json_decode($request->getContent(), true) ?? [];
        $title       = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');

        if ($title === '' || $description === '') {
            return $this->json(['error' => 'Titre et description requis'], Response::HTTP_BAD_REQUEST);
        }

        $pending = $em->getRepository(ThemeRequest::class)->count([
            'requestedBy' => $user,
            'status'      => 'pending',
        ]);
        if ($pending >= 4) {
            return $this->json(['error' => 'Vous avez déjà 4 demandes en attente'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $req = new ThemeRequest();
        $req->setRequestedBy($user)->setStatus('pending')
            ->setTitle($title)->setDescription($description);
        $em->persist($req);
        $em->flush();

        return $this->json(['success' => true], Response::HTTP_CREATED);
    }
}
