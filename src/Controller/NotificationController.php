<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Form\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class NotificationController extends AbstractController
{
    private $token;

    public function __construct(
        private FormFactoryInterface $ff,
        private TokenStorageInterface $tok,
        private EntityManagerInterface $em
    ) {
        $this->token = $tok->getToken();
    }

    #[IsGranted('ROLE_DEMANDEUR')]
    #[Route('/notifications', name: 'notifications', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $em = $this->em;
        $notificationsRepository = $em->getRepository(Notification::class);
        $notifications = $notificationsRepository->findAll();
        $form = $this->ff->createNamed('notifications', NotificationType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            foreach ($notifications as $notif) {
                if (!$notif->isLu()) {
                    $notif->setLu(true);
                }
            }
            $em->flush();
        }

        return $this->render(
            'notification/notification_list.html.twig',
            [
                'form' => $form->createView(),
                'notifications' => $notifications,
            ]
        );
    }
}
