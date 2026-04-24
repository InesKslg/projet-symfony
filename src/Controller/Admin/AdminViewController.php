<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Photos;
use App\Entity\Themes;
use App\Entity\ThemeRequest;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;

class AdminViewController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(ThemeRequestCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Mon Espace Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-user', User::class);
        yield MenuItem::linkToCrud('Photos', 'fa fa-image', Photos::class);
        yield MenuItem::linkToCrud('Thèmes', 'fa fa-tag', Themes::class);
        yield MenuItem::linkToCrud('Demandes de thèmes', 'fa fa-envelope', ThemeRequest::class);
    }
}
