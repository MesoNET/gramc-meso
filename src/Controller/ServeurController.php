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

use App\Entity\Projet;
use App\Entity\Serveur;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceUsers;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Serveur controller.
 */
#[Route(path: 'serveur')]
class ServeurController extends AbstractController
{
    public function __construct(private AuthorizationCheckerInterface $ac,
        private ServiceJournal $sj,
        private ServiceUsers $su,
        private EntityManagerInterface $em)
    {
    }

    /**
     */
    #[isGranted('ROLE_OBS')]
    #[Route(path: '/gerer', name: 'gerer_serveurs', methods: ['GET'])]
    public function gererAction(): Response
    {
        $ac = $this->ac;
        $em = $this->em;

        // Si on n'est pas admin on n'a pas accès au menu
        $menu = $ac->isGranted('ROLE_ADMIN') ? [['ok' => true, 'name' => 'ajouter_serveur', 'lien' => 'Ajouter un serveur', 'commentaire' => 'Ajouter un serveur']] : [];

        return $this->render(
            'serveur/liste.html.twig',
            [
            'menu' => $menu,
            'serveurs' => $em->getRepository(serveur::class)->findBy([], ['nom' => 'ASC']),
            ]
        );
    }

    /**
     * Nouveau serveur.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/ajouter', name: 'ajouter_serveur', methods: ['GET', 'POST'])]
    public function ajouterAction(Request $request): Response
    {
        $su = $this->su;
        $em = $this->em;

        $serveur = new serveur();
        $form = $this->createForm('App\Form\ServeurType', $serveur, ['ajouter' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($serveur);
            $ok = true;
            try {
                $em->flush($serveur);
            } catch (\Exception $e) {
                $ok = false;
                $request->getSession()->getFlashbag()->add('flash erreur', "Le serveur n'a pas été créé (nom du serveur ou de l'utilisateur API ?");
            }

            if ($ok) {
                // Créer les User pour les collaborateurs et versions active ou dernière des projets en état RENOUVELABLE
                $projets = $em->getRepository(Projet::class)->findNonTermines();
                foreach ($projets as $p) {
                    $versions = [];
                    if (null != $p->getVersionDerniere()) {
                        $versions[] = $p->getVersionDerniere();
                    }
                    if (null != $p->getVersionActive()) {
                        $versions[] = $p->getVersionActive();
                    }
                    foreach ($versions as $v) {
                        foreach ($v->getCollaborateurVersion() as $cv) {
                            // Création du User si pas encore fait !
                            $su->getUser($cv->getCollaborateur(), $p, $serveur);
                        }
                    }
                }
            }

            return $this->redirectToRoute('gerer_serveurs');
        }

        return $this->render(
            'serveur/ajouter.html.twig',
            [
            'menu' => [[
                        'ok' => true,
                        'name' => 'gerer_serveurs',
                        'lien' => 'Retour vers la liste des serveurs',
                        'commentaire' => 'Retour vers la liste des serveurs',
                        ]],
            'serveur' => $serveur,
            'form' => $form->createView(),
            ]
        );
    }

    /**
     * Modifier un serveur.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/modifier', name: 'modifier_serveur', methods: ['GET', 'POST'])]
    public function modifierAction(Request $request, Serveur $serveur): Response
    {
        $em = $this->em;
        $editForm = $this->createForm('App\Form\ServeurType', $serveur, ['modifier' => true]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $em->flush();
            } catch (\Exception $e) {
                $request->getSession()->getFlashbag()->add('flash erreur', "Le serveur n'a pas été modifié (nom api ?)");
            }

            return $this->redirectToRoute('gerer_serveurs');
        }

        return $this->render(
            'serveur/modif.html.twig',
            [
            'menu' => [[
                        'ok' => true,
                        'name' => 'gerer_serveurs',
                        'lien' => 'Retour vers la liste des serveurs',
                        'commentaire' => 'Retour vers la liste des serveurs',
                        ]],
            'serveur' => $serveur,
            'edit_form' => $editForm->createView(),
            ]
        );
    }

    /**
     * Supprimer un serveur.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/supprimer', name: 'supprimer_serveur', methods: ['GET'])]
    public function supprimerAction(Request $request, Serveur $serveur): Response
    {
        $em = $this->em;
        $sj = $this->sj;

        $request->getSession()->getFlashbag()->add('flash erreur', 'Fonctionnalité non implémentée');

        return $this->redirectToRoute('gerer_serveurs');

        try {
            $em->remove($serveur);
            $em->flush($serveur);
        } catch (\Exception $e) {
            $request->getSession()->getFlashbag()->add('flash erreur', 'Pas possible de supprimer ce serveur actuellement');
            $sj->errorMessage(__METHOD__.':'.__LINE__.' : '.$e->getMessage());
        }

        return $this->redirectToRoute('gerer_serveurs');
    }
}
