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

use App\Entity\Projet;
use App\Entity\Version;
use App\Entity\Session;
use App\Entity\CollaborateurVersion;
use App\Entity\Formation;
use App\Entity\User;
use App\Entity\Thematique;
use App\Entity\Rattachement;
use App\Entity\Expertise;
use App\Entity\Individu;
use App\Entity\Sso;
use App\Entity\CompteActivation;
use App\Entity\Journal;
use App\Entity\Compta;

use App\GramcServices\Workflow\Projet\ProjetWorkflow;
use App\GramcServices\Workflow\Version\VersionWorkflow;
use App\GramcServices\ServiceIndividus;
use App\GramcServices\ServiceMenus;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceNotifications;
use App\GramcServices\ServiceProjets;
use App\GramcServices\ServiceSessions;
use App\GramcServices\ServiceVersions;
use App\GramcServices\ServiceUsers;
use App\GramcServices\ServiceExperts\ServiceExperts;
use App\GramcServices\GramcDate;
use App\GramcServices\GramcGraf\CalculTous;
use App\GramcServices\GramcGraf\Stockage;
use App\GramcServices\GramcGraf\Calcul;

use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Config\Definition\Exception\Exception;
use App\Utils\Functions;
use App\GramcServices\Etat;
use App\GramcServices\Signal;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Doctrine\ORM\EntityManagerInterface;

use Twig\Environment;

/**
 * Projet controller.
 *
 * Les méthodes liées aux projets mais SPECIFIQUES à un mésocentre particulier
 *
 *
 * @Route("projet")
 */
 // Les controleurs qui se trouvent dans ce fichier peuvent être
 // légèrement différents sur les différents Mesocentres
 // La partie commune se trouve dans le fichier Projetcontroller

class ProjetSpecController extends AbstractController
{
    private $token;
    public function __construct(
        private ServiceIndividus $sid,
        private ServiceJournal $sj,
        private ServiceMenus $sm,
        private ServiceProjets $sp,
        private ServiceSessions $ss,
        private ServiceUsers $su,
        private Calcul $gcl,
        private Stockage $gstk,
        private CalculTous $gall,
        private GramcDate $sd,
        private ServiceVersions $sv,
        private ServiceExperts $se,
        private ProjetWorkflow $pw,
        private FormFactoryInterface $ff,
        private TokenStorageInterface $tok,
        private Environment $tw,
        private AuthorizationCheckerInterface $ac,
        private EntityManagerInterface $em
    ) {
        $this->token = $tok->getToken();
    }

    /**
     * Montre les projets d'un utilisateur
     *
     * @Route("/accueil", name="projet_accueil",methods={"GET"})
     * Method("GET")
     * @Security("is_granted('ROLE_DEMANDEUR')")
     */
    public function accueilAction()
    {
        $sm                  = $this->sm;
        $ss                  = $this->ss;
        $sp                  = $this->sp;
        $su                  = $this->su;
        $token               = $this->token;
        $sid                 = $this->sid;
        $em                  = $this->em;
        $individu            = $token->getUser();
        $id_individu         = $individu->getIdIndividu();

        $projetRepository    = $em->getRepository(Projet::class);
        $cv_repo             = $em->getRepository(CollaborateurVersion::class);
        $user_repo           = $em->getRepository(User::class);

        $list_projets_collab = $projetRepository-> getProjetsCollab($id_individu, false, true);
        $list_projets_resp   = $projetRepository-> getProjetsCollab($id_individu, true, false);

        $projets_term        = $projetRepository-> get_projets_etat($id_individu, 'TERMINE');

        $passwd = null;
        $pwd_expir = null;

        // Vérifier le profil
        if ($token != null)
        {
            $individu = $token->getUser();
            if (! $sid->validerProfil($individu))
            {
                return $this->redirectToRoute('profil');
            };
        }

        // TODO - Faire en sorte pour que les erreurs soient proprement affichées dans l'API
        // En attendant ce qui suit permet de se dépanner mais c'est franchement dégueu
        //echo '<pre>'.strlen($_SERVER['CLE_DE_CHIFFREMENT'])."\n";
        //echo SODIUM_CRYPTO_SECRETBOX_KEYBYTES.'</pre>';
        //$enc = Functions::simpleEncrypt("coucou");
        //$dec = Functions::simpleDecrypt($enc);
        //echo "$dec\n";

        // TODO - Hou le vilain copier-coller !
        // projets responsable
        $projets_resp  = [];
        foreach ($list_projets_resp as $projet) {
            $versionActive  =   $sp->versionActive($projet);
            if ($versionActive != null) {
                $rallonges = $versionActive ->getRallonge();
                $cpt_rall  = count($rallonges->toArray());
            } else {
                $rallonges = null;
                $cpt_rall  = 0;
            }

            $passwd = null;
            $pwd_expir = null;
            $cv = null;
            if ($versionActive != null)
            {
                $cv    = $cv_repo->findOneBy(['version' => $versionActive, 'collaborateur' => $individu]);
                $loginnames = $su->collaborateurVersion2LoginNames($cv);
                $loginnames['TURPAN']['login'] = $cv->getLogint();
                $loginnames['BOREALE']['login'] = $cv->getLoginb();

                /* GESTION DES MOTS DE PASSE SUPPRIMEE 
                $u     = $user_repo->findOneBy(['loginname' => $login]);
                if ($u==null) {
                    $passwd    = null;
                    $pwd_expir = null;
                } else {
                    $passwd    = $u->getPassword();
                    $passwd    = Functions::simpleDecrypt($passwd);
                    $pwd_expir = $u->getPassexpir();
                } */
            } else {
                $loginnames  = [ 'TURPAN' => ['nom' => 'nologin'], 'BOREALE' => ['nom' => 'nologin']];
                $loginnames['TURPAN']['login'] = false;
                $loginnames['BOREALE']['login'] = false;
            }

            $projets_resp[]   =
            [
                'projet'    => $projet,
                'conso'     => $sp->getConsoCalculP($projet),
                'rallonges' => $rallonges,
                'cpt_rall'  => $cpt_rall,
                'meta_etat' => $sp->getMetaEtat($projet),
                'cv' => $cv,
                'loginnames' => $loginnames,
                'passwd'    => $passwd,
                'pwd_expir' => $pwd_expir
            ];
        }

        // projets collaborateurs
        $projets_collab  = [];
        foreach ($list_projets_collab as $projet) {
            $versionActive = $sp->versionActive($projet);

            if ($versionActive != null) {
                $rallonges = $versionActive ->getRallonge();
                $cpt_rall  = count($rallonges->toArray());
            }
            else
            {
                $rallonges = null;
                $cpt_rall  = 0;
            }

            /*
            if ($cv != null) {
                // TODO - Remonter au niveau du ProjetRepository (fonctions get_projet_etats et getProjetsCollab)
                if ($cv->getDeleted() == true) continue;
                $login = $cv->getLoginname()==null ? 'nologin' : $cv->getLoginname();
                $u     = $user_repo->findOneBy(['loginname' => $login]);
                if ($u==null) {
                    $passwd = null;
                    $pwd_expir = null;
                } else {
                    $passwd = $u->getPassword();
                    $passwd = Functions::simpleDecrypt($passwd);
                    $pwd_expir = $u->getPassexpir();
                }
            } else {
                $login = 'nologin';
                $passwd= null;
                $pwd_expir = null;
            }
            */
            $cv = null;
            if ($versionActive != null)
            {
                $cv = $cv_repo->findOneBy(['version' => $versionActive, 'collaborateur' => $individu]);
                $loginnames = $su->collaborateurVersion2LoginNames($cv);
                $loginnames['TURPAN']['login'] = $cv->getLogint();
                $loginnames['BOREALE']['login'] = $cv->getLoginb();

                /* GESTION DES MOTS DE PASSE SUPPRIMEE 
                $u     = $user_repo->findOneBy(['loginname' => $login]);
                if ($u==null) {
                    $passwd    = null;
                    $pwd_expir = null;
                } else {
                    $passwd    = $u->getPassword();
                    $passwd    = Functions::simpleDecrypt($passwd);
                    $pwd_expir = $u->getPassexpir();
                } */
            }
            else
            {
                $loginnames = [ 'TURPAN' => ['nom' => 'nologin'], 'BOREALE' => ['nom' => 'nologin']];
                $loginnames['TURPAN']['login'] = false;
                $loginnames['BOREALE']['login'] = false;
                $passwd = null;
                $pwd_expir = null;
            }
            
            $projets_collab[] =
                [
                'projet'    => $projet,
                'conso'     => $sp->getConsoCalculP($projet),
                'rallonges' => $rallonges,
                'cpt_rall'  => $cpt_rall,
                'cv' => $cv,
                'meta_etat' => $sp->getMetaEtat($projet),
                'loginnames' => $loginnames,
                'passwd'    => $passwd,
                'pwd_expir' => $pwd_expir
                ];
        }

        $menu[] = $this->sm->nouveauProjet(Projet::PROJET_DYN, ServiceMenus::HPRIO);

        return $this->render(
            'projet/demandeur.html.twig',
            [
                'projets_collab'  => $projets_collab,
                'projets_resp'    => $projets_resp,
                'projets_term'    => $projets_term,
                'menu'            => $menu,
                ]
        );
    }

    /**
     * Affiche un projet avec un menu pour choisir la version
     *
     * @Route("/{id}/consulter", name="consulter_projet",methods={"GET","POST"})
     * @Route("/{id}/consulter/{version}", name="consulter_version",methods={"GET","POST"})
     * 
     * Method({"GET","POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     */

    public function consulterAction(Request $request, Projet $projet, Version $version = null)
    {
        $em = $this->em;
        $sp = $this->sp;
        $sj = $this->sj;
        $su = $this->su;
        $coll_vers_repo= $em->getRepository(CollaborateurVersion::class);
        $token = $this->token;

        // On récupère warn_type depuis la session, où il peut avoir été sauvegardé !
        $warn_type = $request->getSession()->get('warn_type');
        if (empty($warn_type)) $warn_type = 0;
        
        // choix de la version
        if ($version == null)
        {
            $version =  $projet->getVersionDerniere();
            if ($version == null)
            {
                $sj->throwException(__METHOD__ . ':' . __LINE__ .' Projet ' . $projet . ': la dernière version est nulle !');
            }
        }
        else
        {
            $projet =   $version->getProjet();
        } // nous devons être sûrs que le projet corresponde à la version

        if (! $sp->projetACL($projet))
        {
            $sj->throwException(__METHOD__ . ':' . __LINE__ .' problème avec ACL');
        }

        // Calcul du loginname, pour affichage de la conso
        $loginnames = null;
        $cv = $coll_vers_repo->findOneBy(['version' => $version, 'collaborateur' => $token->getUser()]);
        if ($cv != null)
        {
            $loginnames = $su->collaborateurVersion2LoginNames($cv);
        }

        // LA SUITE DEPEND DU TYPE DE PROJET !
        // Même affichage pour projets de type 2 et 3 (projets test => projets fil)
        // Affichage pour projets de type 1
        // Affichege pour projets de type 4
        $type = $projet->getTypeProjet();
        switch ($type) {
            case Projet::PROJET_SESS:
                return $this->consulterType1($projet, $version, $loginnames, $request, $warn_type);
            case Projet::PROJET_TEST:
                return $this->consulterType2($projet, $version, $loginnames, $request, $warn_type);
            case Projet::PROJET_FIL:
                return $this->consulterType3($projet, $version, $loginnames, $request, $warn_type);
            case Projet::PROJET_DYN:
                return $this->consulterType4($projet, $version, $loginnames, $request);
            default:
                $sj->errorMessage(__METHOD__ . " Type de projet inconnu: $type");
        }
    }

    // Consulter les projets de type 1
    private function consulterType1(Projet $projet, Version $version, array $loginname, Request $request, $warn_type)
    {
        $em = $this->em;
        $token = $this->token;
        $sm = $this->sm;
        $sp = $this->sp;
        $ac = $this->ac;
        $sv = $this->sv;
        $sj = $this->sj;
        $ff = $this->ff;
        $moi = $token->getUser();

        $session_form = Functions::createFormBuilder($ff, ['version' => $version ])
        ->add(
            'version',
            EntityType::class,
            [
            'multiple' => false,
            'class'    => Version::class,
            'required' =>  true,
            'label'    => '',
            'choices'  =>  $projet->getVersion(),
            'choice_label' => function ($version) {
                return $version->getSession();
            }
            ]
        )
        ->add('submit', SubmitType::class, ['label' => 'Changer'])
        ->getForm();
        
        $session_form->handleRequest($request);

        if ($session_form->isSubmitted() && $session_form->isValid()) {
            $version = $session_form->getData()['version'];
        }

        $session = null;
        if ($version != null) {
            $session = $version->getSession();
        } else {
            $sj->throwException(__METHOD__ . ':' . __LINE__ .' projet ' . $projet . ' sans version');
        }

        $menu = [];
        if ($ac->isGranted('ROLE_ADMIN')) {
            $menu[] = $sm->nouvelleRallonge($projet);
        }

        $menu[] = $sm->renouvelerVersion($version);
        $menu[] = $sm->modifierVersion($version);
        $menu[] = $sm->envoyerEnExpertise($version);
        $menu[] = $sm->changerResponsable($version);
        $menu[] = $sm->gererPublications($projet);
        $menu[] = $sm->modifierCollaborateurs($version);

        if ($this->getParameter('nodata')==false) {
            $menu[] = $sm->donnees($version);
        }
        $menu[] = $sm->telechargerFiche($version);
        $menu[] = $sm->televerserFiche($version);

        $etat_version = $version->getEtatVersion();
        if ($this->getParameter('rapport_dactivite')) {
            if (($etat_version == Etat::ACTIF || $etat_version == Etat::TERMINE) && ! $sp->hasRapport($projet, $version->getAnneeSession())) {
                $menu[] = $sm->telechargerModeleRapportDactivite($version,ServiceMenus::BPRIO);
            }
        }
        $img_expose = [
            $sv->imageProperties('img_expose_1', 'Figure 1', $version),
            $sv->imageProperties('img_expose_2', 'Figure 2', $version),
            $sv->imageProperties('img_expose_3', 'Figure 3', $version),
        ];
        $document     = $sv->getdocument($version);

        $img_justif_renou = [
            $sv->imageProperties('img_justif_renou_1', 'Figure 1', $version),
            $sv->imageProperties('img_justif_renou_2', 'Figure 2', $version),
            $sv->imageProperties('img_justif_renou_3', 'Figure 3', $version)
        ];
        
        $toomuch = false;
        if ($session->getLibelleTypeSession()=='B' && ! $sv->isNouvelle($version)) {
            $version_prec = $version->versionPrecedente();
            if ($version_prec->getAnneeSession() == $version->getAnneeSession()) {
                $toomuch  = $sv -> is_demande_toomuch($version_prec->getAttrHeures(), $version->getDemHeures());
            }
        }
        $rapport_1 = $sp -> getRapport($projet, $version->getAnneeSession() - 1);
        $rapport   = $sp -> getRapport($projet, $version->getAnneeSession());

        $formation = $sv->buildFormations($version);

        if ($projet->getTypeProjet() == Projet::PROJET_SESS) {
            $tmpl = 'projet/consulter_projet_sess.html.twig';
        } else {
            $tmpl = 'projet/consulter_projet_fil.html.twig';
        }

        $cv = $em->getRepository(CollaborateurVersion::class)
                 ->findOneBy(['version' => $version, 'collaborateur' => $moi]);
        
        return $this->render(
            $tmpl,
            [
                'warn_type'          => $warn_type,
                'projet'             => $projet,
                'loginnames'          => $loginnames,
                'version_form'       => $session_form->createView(),
                'version'            => $version,
                'session'            => $session,
                'menu'               => $menu,
                'img_expose'         => $img_expose,
                'img_justif_renou'   => $img_justif_renou,
                'conso_cpu'          => $sp->getConsoRessource($projet, 'cpu', $version->getAnneeSession()),
                'conso_gpu'          => $sp->getConsoRessource($projet, 'gpu', $version->getAnneeSession()),
                'rapport_1'          => $rapport_1,
                'rapport'            => $rapport,
                'document'           => $document,
                'toomuch'            => $toomuch,
                'cv'                 => $cv,
                'formation'          => $formation
            ]
        );
    }

    // Consulter les projets de type 2 (projets test, en voie de disparition)
    // Même chose que Type3, moins le lien Transformer et Renouveler
    private function consulterType2(Projet $projet, Version $version, array $loginnames, Request $request)
    {
        $sm = $this->sm;
        $sp = $this->sp;
        $ac = $this->ac;

        if ($ac->isGranted('ROLE_ADMIN')) {
            $menu[] = $sm->nouvelleRallonge($projet);
        }
        $menu[] = $sm->modifierVersion($version);
        $menu[] = $sm->envoyerEnExpertise($version);
        $menu[] = $sm->modifierCollaborateurs($version);

        return $this->render(
            'projet/consulter_projet_test.html.twig',
            [
            'projet'      => $projet,
            'version'     => $version,
            'session'     => $version->getSession(),
            'consocalcul' => $sp->getConsoCalculVersion($version),
            'quotacalcul' => $sp->getQuotaCalculVersion($version),
            'conso_cpu'   => $sp->getConsoRessource($projet, 'cpu', $version->getAnneeSession()),
            'conso_gpu'   => $sp->getConsoRessource($projet, 'gpu', $version->getAnneeSession()),
            'menu'        => $menu,
            ]
        );
    }

    // Consulter les projets de type 3 (projets fil, c-a-d nouveaux projets test)
    private function consulterType3(Projet $projet, Version $version, array $loginnames, Request $request)
    {
        $sm = $this->sm;
        $sp = $this->sp;
        $ac = $this->ac;

        if ($ac->isGranted('ROLE_ADMIN')) {
            $menu[] = $sm->nouvelleRallonge($projet);
        }
        $menu[] = $sm->transformerProjet($projet);
        $menu[] = $sm->modifierVersion($version);
        $menu[] = $sm->envoyerEnExpertise($version);
        $menu[] = $sm->modifierCollaborateurs($version);

        return $this->render(
            'projet/consulter_projet_test.html.twig',
            [
            'projet'      => $projet,
            'version'     => $version,
            'session'     => $version->getSession(),
            'consocalcul' => $sp->getConsoCalculVersion($version),
            'quotacalcul' => $sp->getQuotaCalculVersion($version),
            'conso_cpu'   => $sp->getConsoRessource($projet, 'cpu', $version->getAnneeSession()),
            'conso_gpu'   => $sp->getConsoRessource($projet, 'gpu', $version->getAnneeSession()),
            'menu'        => $menu,
            ]
        );
    }

    // Consulter les projets de type 4
    private function consulterType4(Projet $projet, Version $version, array $loginnames=null, Request $request)
    {
        $em = $this->em;
        $sm = $this->sm;
        $sp = $this->sp;
        $ac = $this->ac;
        $sv = $this->sv;
        $sj = $this->sj;
        $ff = $this->ff;
        $ss = $this->ss;
        $token = $this->token;
        $moi = $token->getUser();

        //$session_form = Functions::createFormBuilder($ff, ['version' => $version ])
        //->add(
            //'version',
            //EntityType::class,
            //[
            //'multiple' => false,
            //'class'    => Version::class,
            //'required' =>  true,
            //'label'    => '',
            //'choices'  =>  $projet->getVersion(),
            //'choice_label' => function ($version) {
                //return $version->getSession();
            //}
            //]
        //)
        //->add('submit', SubmitType::class, ['label' => 'Changer'])
        //->getForm();
        
        //$session_form->handleRequest($request);

        //if ($session_form->isSubmitted() && $session_form->isValid()) {
            //$version = $session_form->getData()['version'];
        //}

        //$session = null;
        //if ($version != null) {
            //$session = $version->getSession();
        //} else {
            //$sj->throwException(__METHOD__ . ':' . __LINE__ .' projet ' . $projet . ' sans version');
        //}

        $data = $ss->selectAnnee($request);
        
        $menu = [];
        if ($ac->isGranted('ROLE_ADMIN')) {
            $menu[] = $sm->nouvelleRallonge($projet);
        }

        $menu[] = $sm->renouvelerVersion($version);
        $menu[] = $sm->modifierVersion($version);
        $menu[] = $sm->envoyerEnExpertise($version);
        $menu[] = $sm->changerResponsable($version);
        $menu[] = $sm->gererPublications($projet);
        $menu[] = $sm->modifierCollaborateurs($version);

        $menu[] = $sm->telechargerFiche($version);
        $menu[] = $sm->televerserFiche($version);

        $etat_version = $version->getEtatVersion();
        if ($this->getParameter('rapport_dactivite')) {
            if (($etat_version == Etat::ACTIF || $etat_version == Etat::TERMINE) && ! $sp->hasRapport($projet, $version->getAnneeSession())) {
                $menu[] = $sm->telechargerModeleRapportDactivite($version,ServiceMenus::BPRIO);
            }
        }
        $img_expose = [
            $sv->imageProperties('img_expose_1', 'Figure 1', $version),
            $sv->imageProperties('img_expose_2', 'Figure 2', $version),
            $sv->imageProperties('img_expose_3', 'Figure 3', $version),
        ];
        $document     = $sv->getdocument($version);

        $img_justif_renou = [
            $sv->imageProperties('img_justif_renou_1', 'Figure 1', $version),
            $sv->imageProperties('img_justif_renou_2', 'Figure 2', $version),
            $sv->imageProperties('img_justif_renou_3', 'Figure 3', $version)
        ];
        
        //$toomuch = false;
        //if ($session->getLibelleTypeSession()=='B' && ! $sv->isNouvelle($version)) {
            //$version_prec = $version->versionPrecedente();
            //if ($version_prec->getAnneeSession() == $version->getAnneeSession()) {
                //$toomuch  = $sv -> is_demande_toomuch($version_prec->getAttrHeures(), $version->getDemHeures());
            //}
        //}
        //$rapport_1 = $sp -> getRapport($projet, $version->getAnneeSession() - 1);
        //$rapport   = $sp -> getRapport($projet, $version->getAnneeSession());

        $formation = $sv->buildFormations($version);

        $tmpl = 'projet/consulter_projet_sess.html.twig';

        $cv = $em->getRepository(CollaborateurVersion::class)
             ->findOneBy(['version' => $version, 'collaborateur' => $moi]);
        
        return $this->render(
            $tmpl,
            [
                'warn_type' => false,
                'projet' => $projet,
                'loginnames' => $loginnames,
                //'version_form' => $session_form->createView(),
                'version' => $version,
                'session' => null,
                'menu' => $menu,
                'img_expose' => $img_expose,
                'img_justif_renou' => $img_justif_renou,
               // 'conso_cpu'          => $sp->getConsoRessource($projet, 'cpu', $version->getAnneeSession()),
               // 'conso_gpu'          => $sp->getConsoRessource($projet, 'gpu', $version->getAnneeSession()),
                //'rapport_1'          => $rapport_1,
                //'rapport'            => $rapport,
                'document' => $document,
                'toomuch' => false,
                'formation' => $formation,
                'cv' => $cv
            ]
        );
    }
    
    /**
     * Envoie un écran d'explication avant de transformer un projet Fil (=test)
     *
     * @Route("/avant_transformer/{projet}", name="avant_transformer",methods={"GET","POST"})
     * Method({"GET", "POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     *
     */
    public function avantTransformerAction(Request $request, Projet $projet)
    {
        $sm = $this->sm;
        $sj = $this->sj;
        $ss = $this->ss;
        $token = $this->token;

        $m = $sm->transformerProjet($projet);
        if ($m['ok'] == false) {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " impossible de transformer le projet car " . $m['raison']);
        }

        $session = $ss->getSessionCourante();
        $projetRepository = $this->em->getRepository(Projet::class);
        $id_individu      = $token->getUser()->getIdIndividu();

        return $this->render(
            'projet/avant_transformer.html.twig',
            [
            'projet' => $projet,
            ]
        );
    }

    /**
     * Transforme un projet Fil (=Test) en projet de session
     *
     * @Route("/transformer/{projet}", name="transformer",methods={"GET","POST"})
     * Method({"GET", "POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     *
     */
    public function TransformerAction(Request $request, Projet $projet)
    {
        $em = $this->em;

        $m = $sm->transformerProjet($projet);
        if ($m['ok'] == false) {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " impossible de transformer le projet car " . $m['raison']);
        }

        $projet->setTypeProjet(Projet::PROJET_SESS);
        $em->persist($projet);
        $em->flush();
        
        return $this->redirectToRoute('consulter_projet', ['id' => $projet]);
    }

}
