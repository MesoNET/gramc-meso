<?php

/**
 * This file is part of GRAMC (Computing Ressource Granting Software)
 * GRAMC stands for : Gestion des Ressources et de leurs Attributions pour Mésocentre de Calcul.
 *
 * GRAMC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 *  GRAMC is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with GRAMC.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  authors : Miloslav Grundmann - C.N.R.S. - UMS 3667 - CALMIP
 *            Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace App\Controller;

use App\Entity\Individu;
use App\Entity\Projet;
use App\Entity\Version;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceMenus;
use App\GramcServices\ServiceNotifications;
use App\GramcServices\ServiceProjets;
use App\GramcServices\ServiceSessions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DefaultController extends AbstractController
{
    public function __construct(
        private ServiceNotifications $sn,
        private ServiceJournal $sj,
        private ServiceMenus $sm,
        private ServiceProjets $sp,
        private ServiceSessions $ss,
        private FormFactoryInterface $ff,
        private EntityManagerInterface $em
    ) {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/test', name: 'test')]
    public function testAction(Request $request)
    {
        $em = $this->em;
        $em->getRepository(Projet::class)->findOneBy(['idProjet' => 'P1440']);

        $query = $em->createQuery('SELECT partial u.{idIndividu,nom} AS individu, partial s.{eppn} AS sso, count(s) AS score FROM App\Entity\Individu u JOIN u.sso s GROUP BY u');
        $result = $query->getResult();

        return new Response(get_class($result[0]['individu']));

        return new Response(gettype($result[0]['individu']));

        return new Response(implode(' ', array_keys($result[0])));

        return new Response($result[0]['score']);

        if ('array' == gettype($result)) {
            return new Response(gettype(end($result)));
        } else {
            return new Response(gettype($result));
        }

        return new Response(implode(' ', array_keys($result)));
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/twig', name: 'twig')]
    public function twigAction(Request $request)
    {
        $sn = $this->sn;
        $em = $this->em;

        $users = ['a@x', 'b@x'];
        $users = $em->getRepository(Individu::class)->findBy(['president' => true]);
        $versions = $em->getRepository(Version::class)->findAll();
        $users = $sn->mailUsers(['E', 'R'], $versions[20]);
        // $sn->sendMessage('projet/dialog_back.html.twig', 'projet/dialog_back.html.twig', ['projet' => ['idProjet' => 'ID']], $users);
        $sn->sendNotificationTemplate('MESSONET choix édition', 'projet/dialog_back.html.twig', ['projet' => ['idProjet' => 'ID']], $users, 'accueil');

        // return new Response ( $users[0] );
        return new Response();
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/test_params/{id1}/{id2}', name: 'test_params')]
    public function test_paramsAction(Request $request)
    {
        return new Response('ok');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/test_session', name: 'test_session')]
    public function test_sessionAction(Request $request)
    {
        $ss = $this->ss;
        var_dump($ss->getSessionCourante());

        return new Response('OK');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/test_form', name: 'test_session')]
    public function test_formAction(Request $request)
    {
        $form = $this->ff
                   ->createNamedBuilder('image_form', FormType::class, [])
                   ->add('image', TextType::class, ['required' => false])
                   ->add('number', TextType::class, ['required' => false])
                   ->getForm();

        $form->handleRequest($request);

        // if ($form->isSubmitted() )
        print_r($_POST, true);

        return $this->render(
            'version/test_form.html.twig',
            [
                'form' => $form->createView(),
                'print' => print_r($_POST, true),
            ]
        );
    }
}
