<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Photo;

final class UploadController extends AbstractController
{
    #[Route('/upload/photo', name: 'app_upload_photo', methods: ['POST'])]
    public function uploadPhoto(Request $request, EntityManagerInterface $em): Response
    {
        $photo = new Photo();

        $photo->setPhotoUrl($request->request->get('photo_url'));
        $photo->setDescription($request->request->get('description'));
        $photo->setPublic($request->request->get('public') ? true : false);
        $photo->setUser($this->getUser());

        $em->persist($photo);
        $em->flush();

        $this->addFlash('success', 'Photo ajoutée !');

        return $this->redirectToRoute('app_welcome');
    }
}
