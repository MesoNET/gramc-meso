<?php

/**
 * This file is part of GRAMC (Computing Ressource Granting Software)
 * GRAMC stands for : Gestion des Ressources et de leurs Attributions pour Mésocentre de Calcul
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

use App\Entity\Ressource;
use App\Entity\Projet;
use App\Entity\Dac;

use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceDacs;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\EntityManagerInterface;


/**
 * Serveur controller.
 *
 * @Route("/ressource")
 */
class RessourceController extends AbstractController
{
    public function __construct(private AuthorizationCheckerInterface $ac, private ServiceDacs $sd, private ServiceJournal $sj, private EntityManagerInterface $em) {}

    /**
     * @Route("/gerer",name="gerer_ressources", methods={"GET"} )
     * @Security("is_granted('ROLE_OBS')")
     */
    public function gererAction(): Response
    {
        $ac = $this->ac;
        $em = $this->em;

        // Si on n'est pas admin on n'a pas accès au menu
        $menu = $ac->isGranted('ROLE_ADMIN') ? [ ['ok' => true,'name' => 'ajouter_ressource' ,'lien' => 'Ajouter une ressource','commentaire'=> 'Ajouter une ressource'] ] : [];

        return $this->render(
            'ressource/liste.html.twig',
            [
            'menu' => $menu,
            'ressources' => $em->getRepository(ressource::class)->findBy([], ['nom' => 'ASC'])
            ]
        );
    }

    /**
     * Nouvelle ressource
     *
     * @Route("/ajouter", name="ajouter_ressource", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Method({"GET", "POST"})
     */
    public function ajouterAction(Request $request): Response
    {
        $sd = $this->sd;
        $em = $this->em;

        $ressource = new ressource();
        $form = $this->createForm('App\Form\RessourceType', $ressource, ['ajouter' => true ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($ressource);
            $em->flush($ressource);

            // Créer les User pour les versions active ou dernière des projets en état RENOUVELABLE
            $projets = $em->getRepository(Projet::class)->findNonTermines();
            foreach ($projets as $p)
            {
                $versions = [];
                if ($p->getVersionDerniere() != null) $versions[] = $p->getVersionDerniere();
                if ($p->getVersionActive() != null) $versions[] = $p->getVersionActive();
                foreach ($versions as $v)
                {
                    $sd->getDac($v,$ressource);
                }
            }

            return $this->redirectToRoute('gerer_ressources');
        }

        return $this->render(
            'ressource/ajouter.html.twig',
            [
            'menu' => [ [
                        'ok' => true,
                        'name' => 'gerer_ressources',
                        'lien' => 'Retour vers la liste des ressources',
                        'commentaire'=> 'Retour vers la liste des ressources'
                        ] ],
            'ressource' => $ressource,
            'form' => $form->createView(),
            ]
        );
    }

    /**
     * Modifier une ressource
     *
     * @Route("/{id}/modifier_ressource", name="modifier_ressource", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     * 
     */
    public function modifierAction(Request $request, Ressource $ressource): Response
    {
        $editForm = $this->createForm('App\Form\RessourceType', $ressource, ['modifier' => true ]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->em->flush();

            return $this->redirectToRoute('gerer_ressources');
        }

        return $this->render(
            'ressource/modif.html.twig',
            [
            'menu' => [ [
                        'ok' => true,
                        'name' => 'gerer_ressources',
                        'lien' => 'Retour vers la liste des ressources',
                        'commentaire'=> 'Retour vers la liste des ressources'
                        ] ],
            'ressource' => $ressource,
            'edit_form' => $editForm->createView(),
            ]
        );
    }

    /**
     * Supprimer une ressource
     *
     * @Route("/{id}/suppr", name="supprimer_ressource", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function supprimerAction(Request $request, Ressource $ressource): Response
    {
        $em = $this->em;
        $sj = $this->sj;

        try
        {
            $em->remove($ressource);
            $em->flush($ressource);
        }
        catch ( \Exception $e)
        {
            $request->getSession()->getFlashbag()->add("flash erreur", "Pas possible de supprimer cette ressource actuellement");
            $sj->errorMessage(__METHOD__ .':' . __LINE__ . " : " . $e->getMessage());
        }
        return $this->redirectToRoute('gerer_ressources');
    }
}
