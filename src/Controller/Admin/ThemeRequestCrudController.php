<?php

namespace App\Controller\Admin;

use App\Entity\ThemeRequest;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ThemeRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ThemeRequest::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            TextEditorField::new('description', 'Description'),
            TextField::new('status', 'Statut'),
            AssociationField::new('requestedBy', 'Utilisateur'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $validate = Action::new('validate', 'Valider')
            ->linkToCrudAction('validateRequest')
            ->addCssClass('btn btn-success');

        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Action::INDEX, $validate);
    }

    public function validateRequest(
        AdminContext $context,
        EntityManagerInterface $em
    ): RedirectResponse {
        /** @var ThemeRequest $request */
        $request = $context->getEntity()->getInstance();

        // Mise à jour du statut
        $request->setStatus('accepted');

        // Empêcher les doublons de notifications
        $existing = $em->getRepository(Notification::class)->findOneBy([
            'recipient' => $request->getRequestedBy(),
            'message' => "Votre demande de thème « {$request->getTitle()} » a été validée !"
        ]);

        if (!$existing) {
            $notif = new Notification();
            $notif->setRecipient($request->getRequestedBy());
            $notif->setMessage("Votre demande de thème « {$request->getTitle()} » a été validée !");
            $notif->setIsRead(false);
            $em->persist($notif);
        }

        $em->flush();

        $this->addFlash('success', 'Demande validée. Une notification sera affichée à l’utilisateur.');

        // Redirection vers la liste
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
