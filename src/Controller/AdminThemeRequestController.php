<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ThemeRequest;
use App\Form\ThemeRequestType;

class AdminThemeRequestController extends AbstractController
{
    #[Route('/admin/theme/requests', name: 'admin_theme_requests')]
    public function index(EntityManagerInterface $em): Response
    {
        $requests = $em->getRepository(ThemeRequest::class)->findAll();
        return $this->render('admin/theme_requests.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/admin/theme/request/{id}/approve', name: 'admin_theme_request_approve')]
    public function approve(ThemeRequest $themeRequest, EntityManagerInterface $em): Response
    {
        $themeRequest->setStatus('approved');
        $em->flush();
        $this->addFlash('success', 'Thème approuvé !');
        return $this->redirectToRoute('admin_theme_requests');
    }

    #[Route('/admin/theme/request/{id}/reject', name: 'admin_theme_request_reject')]
    public function reject(ThemeRequest $themeRequest, EntityManagerInterface $em): Response
    {
        $themeRequest->setStatus('rejected');
        $em->flush();
        $this->addFlash('error', 'Thème rejeté.');
        return $this->redirectToRoute('admin_theme_requests');
    }
    
}