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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
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
            TextField::new('description', 'Description')->onlyOnIndex(),
            TextEditorField::new('description', 'Description')->onlyOnDetail(),
            ChoiceField::new('status', 'Statut')
                ->setChoices(['En attente' => 'pending', 'Validée' => 'accepted'])
                ->renderAsBadges(['pending' => 'warning', 'accepted' => 'success']),
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
            ->add(Action::INDEX, $validate)
            ->add(Action::INDEX, Action::DETAIL);
    }

    public function validateRequest(
        AdminContext $context,
        EntityManagerInterface $em
    ): RedirectResponse {
        /** @var ThemeRequest $request */
        $request = $context->getEntity()->getInstance();

        $request->setStatus('accepted');

        $title   = $request->getTitle();
        $message = "Votre demande de th\u{00E8}me \u{00AB} {$title} \u{00BB} a \u{00E9}t\u{00E9} valid\u{00E9}e !";

        $existing = $em->getRepository(Notification::class)->findOneBy([
            'recipient' => $request->getRequestedBy(),
            'message'   => $message,
            'isRead'    => false,
        ]);

        if (!$existing) {
            $notif = new Notification();
            $notif->setRecipient($request->getRequestedBy())
                  ->setMessage($message)
                  ->setIsRead(false);
            $em->persist($notif);
        }

        $em->flush();

        $this->addFlash('success', 'Demande validée. Une notification sera affichée à l\'utilisateur.');

        // Redirection vers la liste des demandes
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

}
