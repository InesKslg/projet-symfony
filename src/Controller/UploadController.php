<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\Photos;
use App\Entity\Themes;


final class UploadController extends AbstractController
{
    #[Route('/upload/photo', name: 'app_upload_photo', methods: ['POST'])]
    public function uploadPhoto(Request $request, EntityManagerInterface $em): Response
    {
        $photoFile = $request->files->get('photo_file');

        if(!$photoFile){
            $this->addFlash('error','Aucun fichier envoyé');
            return $this->redirectToRoute('app_welcome');
        }

        $newFilename = uniqid().'.'.$photoFile->guessExtension();

        try {
            $photoFile->move(
                $this->getParameter('kernel.project_dir').'/public/uploads/photos',
                $newFilename
            );
        } catch (FileException $e) {
            $this->addFlash('error','Erreur upload fichier');
            return $this->redirectToRoute('app_welcome');
        }

        $photo = new Photos();

        $photo->setPhotoUrl($newFilename);
        $photo->setDescription($request->request->get('description'));
        $photo->setLocalisation($request->request->get('localisation'));

        $datePrise = $request->request->get('date_prise');
        if ($datePrise) {
            $photo->setDatePrise(new \DateTimeImmutable($datePrise));
        } else {
            $photo->setDatePrise(null);
        }

        $photo->setDateAdded(new \DateTimeImmutable());

        $photo->setPublic($request->request->get('public') ? true : false);

        $photo->setUserPhoto($this->getUser());


        $themeId = $request->request->get('theme_id'); 
        if ($themeId) {
            $theme = $em->getRepository(Themes::class)->find($themeId);
            if ($theme) {
                $photo->addTheme($theme); 
            }
        }

        $em->persist($photo);
        $em->flush();

        $this->addFlash('success', 'Photo ajoutée !');

        return $this->redirectToRoute('app_welcome');
    }
}
