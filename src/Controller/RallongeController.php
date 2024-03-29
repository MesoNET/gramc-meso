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

use Psr\Log\LoggerInterface;

use App\Entity\Rallonge;
use App\Entity\Version;
use App\Entity\Individu;
use App\Entity\Projet;
use App\Entity\Thematique;

use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceExperts\ServiceExpertsRallonge;
use App\GramcServices\ServiceMenus;
use App\GramcServices\ServiceProjets;
use App\GramcServices\ServiceExperts\ServiceExperts;
use App\GramcServices\ServiceRessources;
use App\GramcServices\ServiceSessions;
use App\GramcServices\ServiceVersions;
use App\GramcServices\ServiceRallonges;
use App\GramcServices\Workflow\Rallonge4\Rallonge4Workflow;
use App\GramcServices\Etat;
use App\GramcServices\Signal;
use App\Utils\Functions;
use App\AffectationExperts\AffectationExperts;
use App\AffectationExperts\AffectationExpertsRallonge;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Rallonge controller.
 * @Route("rallonge")
 */
class RallongeController extends AbstractController
{
    public function __construct(
        private ServiceJournal $sj,
        private ServiceMenus $sm,
        private ServiceProjets $sp,
        private ServiceExperts $se,
        private ServiceRessources $sroc,
        private ServiceSessions $ss,
        private ServiceExpertsRallonge $sr,
        private ServiceVersions $sv,
        private ServiceRallonges $srg,
        private Rallonge4Workflow $rw,
        private FormFactoryInterface $ff,
        private ValidatorInterface $vl,
        private EntityManagerInterface $em
    ) {}

    /**
     * Affichage des rallonges dynamiques
     *
     * @Route("/dynamiques", name="rallonge_dynamique", methods={"GET"})
     * @Security("is_granted('ROLE_OBS')")
     */
    public function rallongesDynamiquesAction(): Response
    {
        $em = $this->em;
        $sj = $this->sj;
        $sp = $this->sp;
        $sroc = $this->sroc;

        // On récupère toutes les rallonges des projets dynamiques de cette année
        // Avec des informations statistiques
        $rallonges = $sp->rallongesDynParAnnee();
        $data = [];

        foreach ($rallonges as $r)
        {
            $dars = [];
            foreach ($r->getDar() as $d)
            {
                $dars[$sroc->getNomComplet($d->getRessource())] = $d;
            }
            $data[] = [
                        'rallonge' => $r,
                        'dars' => $dars
            ];
        }

        return $this->render(
            'rallonge/rallonges_dyn.html.twig',
            [
            'data' => $data
            ]
        );
    }

    /**
     * A partir d'une rallonge, renvoie version, projet
     *
     *************************************/
    private function getVerProjSess(Rallonge $rallonge): array
    {
        $version = $rallonge->getVersion();
        $projet = null;
        $session = null;
        if ($version != null) {
            $projet  = $version->getProjet();
        } else {
            $this->sj->throwException(__METHOD__ . ":" . __LINE__ . " rallonge " . $rallonge . " n'est pas associée à une version !");
        }
        return [ $version, $projet ];
    }

    /**
     * Nouvelle rallonge
     *
     * @Route("/{id}/creation", name="nouvelle_rallonge", methods={"GET"})
     * @ Security("is_granted('ROLE_ADMIN')")
     * @Security("is_granted('ROLE_DEMANDEUR')")
     */
    public function creationAction(Request $request, Projet $projet, LoggerInterface $lg): Response
    {
        $sm = $this->sm;
        $ss = $this->ss;
        $sp = $this->sp;

        $sj = $this->sj;
        $srg = $this->srg;
        $em = $this->em;

        // ACL
        if ($sm->nouvelleRallonge($projet)['ok'] == false) {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " impossible de créer une nouvelle rallonge pour le projet" . $projet .
                " parce que : " . $sm->nouvelleRallonge($projet)['raison']);
        }
        
        $version  = $sp->versionActive($projet);
        $rallonge = $srg->creerRallonge($version);

        $request->getSession()->getFlashbag()->add("flash info","Rallonge créée, responsable notifié");
        return $this->redirectToRoute('consulter_rallonge', ['id' => $rallonge]);
    }

    /**
     * Afficher une rallonge
     *
     * @Route("/{id}/consulter", name="consulter_rallonge", methods={"GET"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     * Method("GET")
     */
    public function consulterAction(Request $request, Rallonge $rallonge): Response
    {
        $sm = $this->sm;
        $sp = $this->sp;
        $sj = $this->sj;

        [ $version, $projet ] = $this->getVerProjSess($rallonge);

        // ACL
        if (! $sp->projetACL($projet) || $projet == null) {
            $sj->throwException(__METHOD__ . ':' . __LINE__ .' problème avec ACL');
        }

        $menu[]   = $sm->modifierRallonge($rallonge);
        $menu[]   = $sm->envoyerEnExpertiseRallonge($rallonge);

        return $this->render(
            'rallonge/consulter.html.twig',
            [
            'rallonge'  => $rallonge,
            'projet'    => $projet,
            'version'   => $version,
            'menu'      => $menu
            ]
        );
    }

    /**
    * Modifier une rallonge.
    *
    * @Route("/{id}/modifier", name="modifier_rallonge", methods={"GET","POST"})
    * @Security("is_granted('ROLE_DEMANDEUR')")
    * Method({"GET", "POST"})
    */
    public function modifierAction(Request $request, Rallonge $rallonge): Response
    {
        $sm = $this->sm;
        $sj = $this->sj;
        $sval = $this->vl;
        $srg = $this->srg;
        $em = $this->em;

        // ACL
        if ($sm->modifierRallonge($rallonge)['ok'] == false) {
            $sj->throwException(__METHOD__ . " impossible de modifier la rallonge " . $rallonge->getIdRallonge().
                " parce que : " . $sm->modifierRallonge($rallonge)['raison']);
        }

        // FORMULAIRE DES RESSOURCES
        $ressource_form = $srg->getRessourceForm($rallonge);
        $ressource_form->handleRequest($request);
        $data = $ressource_form->getData();
        $ressource_forms = $data['ressource'];

        // NOTE - On met à zéro les demandes qui sont invalides
        $validated = $srg->validateRessourceForms($ressource_forms);
        if (! $validated)
        {
            $message = "Erreur dans une de vos demandes, elle a été mise à 0";
            $request->getSession()->getFlashbag()->add("flash erreur",$message);
        }
 
        $editForm = $this->createFormBuilder($rallonge)
            ->add('prjJustifRallonge', TextAreaType::class, [ 'required'       =>  false ])
            ->add('enregistrer', SubmitType::class, ['label' => 'Enregistrer' ])
            ->add('fermer', SubmitType::class, ['label' => 'Fermer' ])
            ->add('annuler', SubmitType::class, ['label' => 'Annuler' ])
            ->getForm();

        [ $version, $projet ] = $this->getVerProjSess($rallonge);

        $erreurs = [];
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted()) {
            
            if ($editForm->get('annuler')->isClicked()) {
                return $this->redirectToRoute('consulter_rallonge', [ 'id' => $rallonge->getIdRallonge() ]);
            }
            
            $erreurs = Functions::dataError($sval, $rallonge);
            $em->flush();
            $request->getSession()->getFlashbag()->add("flash info","Rallonge enregistrée");

            if ($editForm->get('fermer')->isClicked()) {
                return $this->redirectToRoute('consulter_rallonge', [ 'id' => $rallonge->getIdRallonge() ]);
            }
        }
        return $this->render(
            'rallonge/modifier.html.twig',
            [
            'rallonge'  => $rallonge,
            'projet'    => $projet,
            'edit_form' => $editForm->createView(),
            'ressource_form' => $ressource_form->createView(),
            'erreurs'   => $erreurs,
        ]
        );
    }

    /**
     *
     * TODO - VIRER CETTE FONCTION
     *
     * @Route("/{id}/avant_envoyer", name="avant_envoyer_rallonge", methods={"GET"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     * Method("GET")
     */
    public function avantEnvoyerAction(Request $request, Rallonge $rallonge): Response
    {
        $sm = $this->sm;
        $sj = $this->sj;
        $sval = $this->vl;

        // ACL
        if ($sm->envoyerEnExpertiseRallonge($rallonge)['ok'] == false) {
            $sj->throwException(__METHOD__ . " impossible d'envoyer la rallonge " . $rallonge->getIdRallonge().
                " à l'expert parce que : " . $sm->envoyerEnExpertiseRallonge($rallonge)['raison']);
        }

        [ $version, $projet ] = $this->getVerProjSess($rallonge);

        $erreurs = Functions::dataError($sval, $rallonge);
        return $this->render(
            'rallonge/avant_envoyer.html.twig',
            [
            'rallonge'  => $rallonge,
            'projet'    => $projet,
            'erreurs'   => $erreurs,
            ]
        );
    }

    /**
     * Envoi d'une rallonge en expertise
     *
     * @Route("/{id}/envoyer", name="envoyer_rallonge", methods={"GET"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     * Method("GET")
     */
    public function envoyerAction(Request $request, Rallonge $rallonge): Response
    {
        $sm = $this->sm;
        $sj = $this->sj;
        $se = $this->se;
        $sval = $this->vl;

        // ACL
        if ($sm->envoyerEnExpertiseRallonge($rallonge)['ok'] == false) {
            $sj->throwException(__METHOD__ . " impossible de modifier la rallonge " . $rallonge->getIdRallonge().
                " parce que : " . $sm->envoyerEnExpertiseRallonge($rallonge)['raison']);
        }

        $erreurs = Functions::dataError($sval, $rallonge);
        $workflow = $this->rw;

        if ($erreurs != null)
        {
            $sj->warningMessage(__METHOD__ . ":" . __LINE__ ." L'envoi à l'expert de la rallonge " . $rallonge . " refusé à cause des erreurs !");
            return $this->redirectToRoute('avant_envoyer_rallonge', [ 'id' => $rallonge->getId() ]);
        }
        elseif (! $workflow->canExecute(Signal::CLK_VAL_DEM, $rallonge))
        {
            $sj->warningMessage(__METHOD__ . ":" . __LINE__ ." L'envoi à l'expert de la rallonge " . $rallonge .
                " refusé par le workflow, la rallonge est dans l'état " . Etat::getLibelle($rallonge->getEtatRallonge()));
            return $this->redirectToRoute('avant_envoyer_rallonge', [ 'id' => $rallonge->getId() ]);
        }

        // Crée une nouvelle expertise
        $se->newExpertiseIfPossible($rallonge);

        $rtn = $workflow->execute(Signal::CLK_VAL_DEM, $rallonge);

        if ($rtn == true)
        {
            $request->getSession()->getFlashbag()->add("flash info","Votre rallonge nous a été envoyée. Vous allez recevoir un courriel de confirmation.");
        }
        else
        {
            $sj->errorMessage(__METHOD__ .  ":" . __LINE__ . " La rallonge " . $rallonge->getIdRallonge() . " n'a pas pu etre envoyée en validation.");
            $request->getSession()->getFlashbag()->add("flash erreur","Votre rallonge n'a pas pu être envoyée en validation. Merci de vous rapprocher du support");
        }

         [ $version, $projet ] = $this->getVerProjSess($rallonge);

        return $this->render(
            'rallonge/envoyer.html.twig',
            [
            'rallonge'  => $rallonge,
            'projet'    => $projet,
        ]
        );
    }
}
