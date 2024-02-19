<?php

namespace App\Controller;

use App\Entity\Individu;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetAvatarController extends AbstractController
{
    #[Route('avatar/{individu}', 'get_avatar')]
    public function GetAvatarAction(Individu $individu): Response
    {
        if ($individu) {
            $response = new Response(
                file_get_contents(Path::join($this->getParameter('kernel.project_dir'), 'var', 'photos', $individu->getPhoto()), -1, null),
                Response::HTTP_OK,
                ['content-type' => 'image/png']
            );

            return $response;
        } else {
            throw $this->createNotFoundException('L\'avatar n\'existe pas.');
        }
    }
}
