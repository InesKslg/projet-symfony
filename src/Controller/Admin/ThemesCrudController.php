<?php

namespace App\Controller\Admin;

use App\Entity\Themes;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ThemesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Themes::class;
    }

    public function persistEntity(EntityManagerInterface $em, mixed $entityInstance): void
    {
        parent::persistEntity($em, $entityInstance);

        $users = $em->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            $notif = new Notification();
            $notif->setRecipient($user);
            $notif->setMessage("Nouveau thème disponible : « {$entityInstance->getNom()} » !");
            $notif->setIsRead(false);
            $em->persist($notif);
        }
        $em->flush();
    }
}
