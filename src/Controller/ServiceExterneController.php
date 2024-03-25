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

use App\Entity\ServiceExterne;
use App\Form\ServiceExterneType;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceUsers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * service_externe controller.
 */
#[Route(path: 'service_externe')]
class ServiceExterneController extends AbstractController
{
    #[IsGranted('ROLE_OBS')]
    #[Route(path: '/gerer', name: 'gerer_service_externes', methods: ['GET'])]
    public function gererAction(AuthorizationCheckerInterface $ac, EntityManagerInterface $em): Response
    {
        // Si on n'est pas admin on n'a pas accès au menu
        $menu = $ac->isGranted('ROLE_ADMIN') ? [['ok' => true, 'name' => 'ajouter_service_externe', 'lien' => 'Ajouter un service externe', 'commentaire' => 'Ajouter un service externe']] : [];

        return $this->render(
            'service_externe/liste.html.twig',
            [
                'menu' => $menu,
                'service_externes' => $em->getRepository(ServiceExterne::class)->findBy([], ['username' => 'ASC']),
            ]
        );
    }

    /**
     * Nouveau service externe.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/ajouter', name: 'ajouter_service_externe', methods: ['GET', 'POST'])]
    public function ajouterAction(Request $request, ServiceUsers $su, EntityManagerInterface $em): Response
    {
        $service_externe = new ServiceExterne();
        $form = $this->createForm(ServiceExterneType::class, $service_externe, ['ajouter' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($service_externe);
            $ok = true;
            try {
                $em->flush($service_externe);
            } catch (\Exception $e) {
                $ok = false;
                $request->getSession()->getFlashbag()->add('flash erreur', "Le service_externe n'a pas été créé (nom du service_externe ou de l'utilisateur API ?");
            }

            return $this->redirectToRoute('gerer_service_externes');
        }

        return $this->render(
            'service_externe/ajouter.html.twig',
            [
                'menu' => [[
                    'ok' => true,
                    'name' => 'gerer_service_externes',
                    'lien' => 'Retour vers la liste des service externes',
                    'commentaire' => 'Retour vers la liste des service externes',
                ]],
                'service_externe' => $service_externe,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Modifier un service externe.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/modifier', name: 'modifier_service_externe', methods: ['GET', 'POST'])]
    public function modifierAction(Request $request, ServiceExterne $service_externe, EntityManagerInterface $em): Response
    {
        $editForm = $this->createForm(ServiceExterneType::class, $service_externe, ['modifier' => true]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $em->flush();
            } catch (\Exception $e) {
                $request->getSession()->getFlashbag()->add('flash erreur', "Le service_externe n'a pas été modifié (nom api ?)");
            }

            return $this->redirectToRoute('gerer_service_externes');
        }

        return $this->render(
            'service_externe/modif.html.twig',
            [
                'menu' => [[
                    'ok' => true,
                    'name' => 'gerer_service_externes',
                    'lien' => 'Retour vers la liste des service_externes',
                    'commentaire' => 'Retour vers la liste des service_externes',
                ]],
                'service_externe' => $service_externe,
                'edit_form' => $editForm->createView(),
            ]
        );
    }

    /**
     * Supprimer un service_externe.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/supprimer', name: 'supprimer_service_externe', methods: ['GET'])]
    public function supprimerAction(Request $request, ServiceExterne $service_externe, EntityManagerInterface $em, ServiceJournal $sj): Response
    {
        try {
            $em->remove($service_externe);
            $em->flush($service_externe);
        } catch (\Exception $e) {
            $request->getSession()->getFlashbag()->add('flash erreur', 'Pas possible de supprimer ce service_externe actuellement');
            $sj->errorMessage(__METHOD__.':'.__LINE__.' : '.$e->getMessage());
        }

        return $this->redirectToRoute('gerer_service_externes');
    }
}
