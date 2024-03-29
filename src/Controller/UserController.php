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

use App\Entity\User;
use App\Entity\Clessh;
use App\Entity\CollaborateurVersion;

use App\Form\UserType;

use App\GramcServices\ServiceJournal;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\EntityManagerInterface;

/**
 * User controller.
 *
 * @Security("is_granted('ROLE_DEMANDEUR')")
 * @Route("user")
 */
class UserController extends AbstractController
{

    public function __construct(
        private ServiceJournal $sj,
        private EntityManagerInterface $em
    ) {}

    /**
     * Modification par le demandeur de la clé ssh liée à ce user
     *
     * NOTE - Le demandeur ne peut PAS CHANGER AUTRE CHOSE
     *
     * @Route("/{id}/modif", name="user_modif",methods={"GET","POST"})
     * 
     */
    public function modifAction(Request $request, User $user): Response
    {
        $em = $this->em;
        
        if ($user == null)
        {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " ERREUR INTERNE: User null");
        }

        $individu = $user->getIndividu();
        $clessh = $em->getRepository(Clessh::class)->findBy(['individu' => $individu, 'rvk' => false]);

        $old_clessh = $user->getClessh();
        $form = $this->createForm(UserType::class, $user, ['clessh' => $clessh] );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->getData()->getCgu() == false)
            {
                $request->getSession()->getFlashbag()->add("flash erreur","Vous devez accepter les CGU");
            }
            else
            {
                // Si on a changé de cle ssh, remettre à false le flag de déploiement
                $new_clessh = $form->getData()->getClessh();
                if ($old_clessh != null)
                {
                    if ($new_clessh != null && $old_clessh->getId() != $new_clessh->getId())
                    {
                        $user->setDeply(false);
                    }
                }
                $em->flush();
                return $this->redirectToRoute('projet_accueil');
            }
        }

        // TODO - Traitement d'erreur si serveur est null
        $serveur_nom = $user->getServeur()->getNom();
        $serveur_cgu = $user->getServeur()->getCguUrl();
        if ($serveur_cgu === null) $serveur_cgu = "";
        
        return $this->render('user/modif.html.twig', array(
            'user' => $user,
            'clessh' => $clessh,
            'form' => $form->createView(),
            'serveur_cgu' => $serveur_cgu,
            'serveur_nom' => $serveur_nom
        ));
    }
}
