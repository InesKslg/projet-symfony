<?php

namespace App\Controller;

use App\Entity\Themes;
use App\Form\ThemesType;
use App\Repository\ThemesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/themes')]
final class ThemesController extends AbstractController
{
    #[Route(name: 'app_themes_index', methods: ['GET'])]
    public function index(ThemesRepository $themesRepository): Response
    {
        return $this->render('themes/index.html.twig', [
            'themes' => $themesRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_themes_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $theme = new Themes();
        $form = $this->createForm(ThemesType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($theme);
            $entityManager->flush();

            return $this->redirectToRoute('app_themes_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('themes/new.html.twig', [
            'theme' => $theme,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_themes_show', methods: ['GET'])]
    public function show(Themes $theme): Response
    {
        return $this->render('themes/show.html.twig', [
            'theme' => $theme,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_themes_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Themes $theme, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ThemesType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_themes_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('themes/edit.html.twig', [
            'theme' => $theme,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_themes_delete', methods: ['POST'])]
    public function delete(Request $request, Themes $theme, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$theme->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($theme);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_themes_index', [], Response::HTTP_SEE_OTHER);
    }
}
