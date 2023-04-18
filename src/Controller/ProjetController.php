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
use App\Entity\CollaborateurVersion;
use App\Entity\Formation;
use App\Entity\User;
use App\Entity\Serveur;
use App\Entity\Thematique;
use App\Entity\Expertise;
use App\Entity\Individu;
use App\Entity\Sso;
use App\Entity\CompteActivation;
use App\Entity\Journal;
use App\Entity\Compta;

use App\GramcServices\Workflow\Projet\ProjetWorkflow;
use App\GramcServices\Workflow\Version\VersionWorkflow;
use App\GramcServices\Workflow\Projet4\Projet4Workflow;
use App\GramcServices\ServiceMenus;
use App\GramcServices\ServiceIndividus;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceNotifications;
use App\GramcServices\ServiceProjets;
use App\GramcServices\ServiceSessions;
use App\GramcServices\ServiceVersions;
use App\GramcServices\ServiceUsers;
use App\GramcServices\ServiceServeurs;
use App\GramcServices\ServiceRessources;
use App\GramcServices\ServiceExperts\ServiceExperts;
use App\GramcServices\GramcDate;

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

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Doctrine\ORM\EntityManagerInterface;

use Twig\Environment;

// Pour le tri numérique sur les années, en commençant par la plus grande - cf. resumesAction
function cmpProj($a, $b)
{
    return intval($a['annee']) < intval($b['annee']);
}

/**
 * Projet controller.
 *
 * @Route("projet")
 */
 // Tous ces controleurs sont exécutés au moins par OBS, certains par ADMIN seulement
 // et d'autres par DEMANDEUR

class ProjetController extends AbstractController
{
    private $token = null;
    
    public function __construct(
        private ServiceIndividus $sid,
        private ServiceJournal $sj,
        private ServiceMenus $sm,
        private ServiceProjets $sp,
        private ServiceSessions $ss,
        private ServiceUsers $su,
        private ServiceServeurs $sr,
        private ServiceRessources $sroc,
        private GramcDate $grdt,
        private ServiceVersions $sv,
        private ServiceExperts $se,
        private Projet4Workflow $pw4,
        private FormFactoryInterface $ff,
        private TokenStorageInterface $tok,
        private Environment $tw,
        private AuthorizationCheckerInterface $ac,
        private EntityManagerInterface $em
    )
    { $this->token = $tok->getToken(); }

    /**
     * Lists all projet entities.
     *
     * @Route("/", name="projet_index", methods={"GET"})
     * @Security("is_granted('ROLE_OBS')")
     */
    public function indexAction(): Response
    {
        $em = $this->em;

        $projets = $em->getRepository(Projet::class)->findAll();

        return $this->render('projet/index.html.twig', array(
            'projets' => $projets,
        ));
    }

    /**
     * Rgpd !
     *
     * Ne fait rien, affiche simplement la commande à exécuter
     *
     * 
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/rgpd", name="rgpd", methods={"GET"})
     * 
     */
    public function rgpdAction(Request $request): Response
    {
        return $this->render('projet/rgpd.html.twig');
    }

    /**
     * fermer un projet
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/fermer", name="fermer_projet", methods={"GET","POST"})
     */
    public function fermerAction(Projet $projet, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $confirmation = $request->request->get('confirmation');

            if ($confirmation == 'OUI') {
                $workflow = $this->pw;
                if ($workflow->canExecute(Signal::CLK_FERM, $projet)) {
                    $workflow->execute(Signal::CLK_FERM, $projet);
                }
            }
            return $this->redirectToRoute('projet_tous'); // NON - on ne devrait jamais y arriver !
        } else {
            return $this->render(
                'projet/dialog_fermer.html.twig',
                [
            'projet' => $projet,
            ]
            );
        }
    }

    /**
     * Retour en arrière: un projet en validation repasse en édition
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/back", name="back_version", methods={"GET","POST"})
     */
    public function backAction(Projet $projet, Request $request): Response
    {
        $se = $this->se;
        $em = $this->em;

        // On travaille sur la DERNIERE version du projet, sinon rien !
        $version = $projet->getVersionDerniere();
        if ($version->getTypeVersion() == Projet::PROJET_DYN)
        {
            $workflow = $this->pw4;
        }
        else
        {
            $workflow = $this->pw;
        }

        if ($request->isMethod('POST'))
        {
            $confirmation = $request->request->get('confirmation');
            if ($confirmation == 'OUI') {
                if ($workflow->canExecute(Signal::CLK_ARR, $version->getProjet()))
                {
                    $rtn = $workflow->execute(Signal::CLK_ARR, $version->getProjet());
                    if ($rtn == true)
                    {
                        $request->getSession()->getFlashbag()->add("flash info","Projet $projet revenu en édition");
                    }
                    else
                    {
                        $request->getSession()->getFlashbag()->add("flash erreur","Le projet $projet n'a pas pu revenir en édition.");
                        $sj->errorMessage(__METHOD__ .  ":" . __LINE__ . " Le projet $projet n'a pas pu revenir en édition.");
                    }

                    // Supprime toutes les expertises
                    $expertises = $version->getExpertise()->toArray();
                    $em = $this->em;
                    foreach ($expertises as $e) {
                        $em->remove($e);
                    }
                    $em->flush();
                }
            }
            return $this->redirectToRoute('projet_dynamique');
        }
        else
        {
            return $this->render(
                'projet/dialog_back.html.twig',
                [
                    'version' => $version,
                ]
            );
        }
    }

    /**
     * L'admin a cliqué sur le bouton Forward pour envoyer une version à l'expert
     * à la place du responsable
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/fwd", name="fwd_version", methods={"GET","POST"})
     */
    public function forwardAction(Projet $projet, Request $request, LoggerInterface $lg): Response
    {
        $se = $this->se;
        $em = $this->em;

        // On travaille sur la DERNIERE version du projet, sinon rien !
        $version = $projet->getVersionDerniere();
        if ($version->getTypeVersion() == Projet::PROJET_DYN)
        {
            $workflow = $this->pw4;
        }
        else
        {
            $workflow = $this->pw;
        }
        if ($request->isMethod('POST'))
        {
            $confirmation = $request->request->get('confirmation');

            if ($confirmation == 'OUI') {
                if ($workflow->canExecute(Signal::CLK_VAL_DEM, $version->getProjet()))
                {
                    // Crée une nouvelle expertise avec proposition d'experts
                    $se->newExpertiseIfPossible($version);

                    // Avance du workflow
                    $rtn = $workflow->execute(Signal::CLK_VAL_DEM, $projet);
                    if ($rtn == true)
                    {
                        $request->getSession()->getFlashbag()->add("flash info","Projet $projet envoyé en validation");
                    }
                    else
                    {
                        $request->getSession()->getFlashbag()->add("flash erreur","Le projet $projet n'a pas pu être envoyé en validation.");
                        $sj->errorMessage(__METHOD__ .  ":" . __LINE__ . " Le projet $projet n'a pas pu être envoyé en validation.");
                    }
                }
            }
            return $this->redirectToRoute('projet_dynamique');
        }
        else
        {
            return $this->render(
                'projet/dialog_fwd.html.twig',
                [
            'version' => $version,
            ]
            );
        }
    }

    /**
     * Résumés de tous les projets qui ont une version cette annee
     *
     * Param : $annee
     *
     * @Security("is_granted('ROLE_OBS')")
     * @Route("/{annee}/resumes", name="projet_resumes", methods={"GET","POST"})
     *
     */
    public function resumesAction($annee): Response
    {
        $sp    = $this->sp;
        $sj    = $this->sj;

        $paa   = $sp->projetsParAnnee($annee);
        $prjs  = $paa[0];
        $total = $paa[1];

        // construire une structure de données:
        //     - tableau associatif indexé par la métathématique
        //     - Pour chaque méta thématique liste des projets correspondants
        //       On utilise version B si elle existe, version A sinon
        //       On garde titre, les deux dernières publications, résumé
        $projets = [];
        foreach ($prjs as $p) {
            $v = empty($p['vb']) ? $p['va'] : $p['vb'];

            // On saute les projets en édition !
            if ($v->getEtatVersion() == Etat::EDITION_DEMANDE) {
                continue;
            }
            $thematique= $v->getPrjThematique();
            $prj  = [];
            $prj['id'] = $v->getProjet()->getIdProjet();
            $prj['titre'] = $v->getPrjTitre();
            $prj['resume']= $v->getPrjResume();
            $prj['laboratoire'] = $v->getLabo();
            $a = $v->getProjet()->getIdProjet();
            $a = substr($a, 1, 2);
            $a = 2000 + intval($a);
            $prj['annee'] = $a;
            $publis = array_slice($v->getProjet()->getPubli()->toArray(), -2, 2);
            //$publis = array_slice($publis, -2, 2); // On garde seulement les deux dernières
            $prj['publis'] = $publis;
            $prj['porteur'] = $v->getResponsable()->getPrenom().' '.$v->getResponsable()->getNom();
            $prjm[] = $prj;
        };

        return $this->render(
            'projet/resumes.html.twig',
            [
                'annee'     => $annee,
                'projets'   => $projets,
                ]
        );
    }

    /**
     * Téléchargement du rapport d'activité
     * @Security("is_granted('ROLE_DEMANDEUR') or is_granted('ROLE_OBS')")
     * @Route("/{id}/rapport/{annee}", defaults={"annee"=0}, name="rapport", methods={"GET"})
     */
    public function rapportAction(Version $version, Request $request, $annee): Response
    {
        $sp = $this->sp;
        $sj = $this->sj;

        if (! $sp->projetACL($version->getProjet())) {
            $sj->throwException(__METHOD__ . ':' . __LINE__ .' problème avec ACL');
        }

        $filename = $sp->getRapport($version->getProjet(), $annee);

        //return new Response($filename);

        if (file_exists($filename)) {
            return Functions::pdf(file_get_contents($filename));
        } else {
            $sj->errorMessage(__METHOD__ . ":" . __LINE__ . " fichier du rapport d'activité \"" . $filename . "\" n'existe pas");
            return Functions::pdf(null);
        }
    }

    /**
     * Téléchargement de la fiche projet qui doit être signée par la direction du laboratoire demandeur
     *
     * @Route("/{id}/signature", name="signature", methods={"GET"})
     * @Security("is_granted('ROLE_OBS')")
     */
    public function signatureAction(Version $version, Request $request): Response
    {
        $sv = $this->sv;
        return Functions::pdf($sv->getSigne($version));
    }

    /**
     * download doc attaché
     *
     * @Route("/{id}/document", name="document", methods={"GET"})
     * @Security("is_granted('ROLE_DEMANDEUR') or is_granted('ROLE_OBS')")
     */
    public function documentAction(Version $version, Request $request): Response
    {
        $sv = $this->sv;
        return Functions::pdf($sv->getDocument($version));
    }

    /**
     * Projets dynamiques
     *
     * @Route("/dynamiques", name="projet_dynamique", methods={"GET","POST"})
     * @Security("is_granted('ROLE_OBS')")
     */
    public function projetsDynamiquesAction(Request $request): Response
    {
        $em = $this->em;
        //$projets = $em->getRepository(Projet::class)->findAll();
        $sj = $this->sj;
        $sp = $this->sp;
        $sroc = $this->sroc;
        $ss = $this->ss;

        $selectAnneeData  = $ss->selectAnnee($request); // formulaire
        $annee = $selectAnneeData['annee'];

        foreach (['termine','standby','accepte','refuse','edition','expertise','nonrenouvele','inconnu'] as $e) {
            $etat_projet[$e] = 0;
        }

        // On récupère tous les projets dynamiques toutes années confondues
        // Avec des informations statistiques
        [$projets_data,$total,$repart] = $sp->projetsDynParAnnee($annee);

        $data = [];
        $collaborateurVersionRepository = $em->getRepository(CollaborateurVersion::class);
        $versionRepository              = $em->getRepository(Version::class);
        $projetRepository               = $em->getRepository(Projet::class);

        foreach ($projets_data as $projet_data) {
            $projet = $projet_data['p'];
            //$info     = $versionRepository->info($projet); // les stats du projet
            $version = $projet->getVersionActive();
            if ($version === null)
            {
                $version = $projet->getVersionDerniere();
                if ($version === null)
                {
                    $sj->errorMessage(__METHOD__ .":" . __LINE__ . "projet $projet - Pas de version !");
                    continue;
                }
            };
            
            $metaetat = strtolower($sp->getMetaEtat($projet));
            $count = intval($projet->getVersionDerniere()->getNbVersion());

            $etat_projet[$metaetat] += 1;

            $dacs=[];
            foreach ($version->getDac() as $d)
            {
                $dacs[$sroc->getNomComplet($d->getRessource())] = $d;
            }
            $data[] = [
                    'projet' => $projet,
                    'renouvelable' => $projet->getEtatProjet()==Etat::RENOUVELABLE,
                    'metaetat' => $metaetat,
                    'version' => $version,
                    'dacs' => $dacs,
                    'etat_version' => ($version != null) ? Etat::getLibelle($version->getEtatVersion()) : 'SANS_VERSION',
                    'count' => $count,
                    'responsable' => $collaborateurVersionRepository->getResponsable($projet),
            ];
        }

        $etat_projet['total']      = $projetRepository->countAll();
    
        return $this->render(
            'projet/projets_dyn.html.twig',
            [
            'form' => $selectAnneeData['form']->createView(), // formulaire de hoix de l'année
            'etat_projet' => $etat_projet,
            'data' => $data,
            'total' => $total
            ]
        );
    }

    /**
     * Pas utilisé...
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/gerer", name="gerer_projets", methods={"GET"})
     * Method("GET")
     */
    public function gererAction(): Response
    {
        $em = $this->em;
        $projets = $em->getRepository(Projet::class)->findAll();

        return $this->render('projet/gerer.html.twig', array(
            'projets' => $projets,
        ));
    }

    /**
     * Envoie un écran de mise en garde avant de créer un nouveau projet (inutilisé)
     *
     * @Route("/avant_nouveau/{type}", name="avant_nouveau_projet", methods={"GET","POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     *
     */
    public function avantNouveauAction(Request $request, int $type): Response
    {
        $sm = $this->sm;
        $sj = $this->sj;
        $ss = $this->ss;
        $token = $this->tok->getToken();

        if ($sm->nouveauProjet($type)['ok'] == false) {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " impossible de créer un nouveau projet parce que " . $sm->nouveauProjet($type)['raison']);
        }

        $projetRepository = $this->em->getRepository(Projet::class);
        $id_individu      = $token->getUser()->getIdIndividu();
        $renouvelables    = $projetRepository-> getProjetsCollab($id_individu, true, true, true);

        if ($renouvelables == null) {
            return  $this->redirectToRoute('nouveau_projet', ['type' => $type]);
        }

        return $this->render(
            'projet/avant_nouveau_projet.html.twig',
            [
            'renouvelables' => $renouvelables,
            'type'          => $type,
            ]
        );
    }

    /**
     * Création d'un nouveau projet
     *
     * @Route("/nouveau/{type}", name="nouveau_projet", methods={"GET","POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     *
     */
    public function nouveauAction(Request $request, $type): Response
    {
        $grdt = $this->grdt;
        $sm = $this->sm;
        $ss = $this->ss;
        $sp = $this->sp;
        $sv = $this->sv;
        $sj = $this->sj;
        $sr = $this->sr;
        $sroc = $this->sroc;
        $su = $this->su;
        $grdt = $this->grdt;
        $token = $this->tok->getToken();
        $em = $this->em;

        // contournement d'un problème lié à Doctrine (???))
        $request->getSession()->remove('SessionCourante'); // remove cache

        // NOTE - Pour ce controleur, on identifie les types par un chiffre (voir Entity/Projet.php)
        $m = $sm->nouveauProjet("$type");
        if ($m == null || $m['ok']==false) {
            $raison = $m===null ? "ERREUR AVEC LE TYPE $type - voir le paramètre prj_type" : $m['raison'];
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " impossible de créer un nouveau projet parce que $raison");
        }

        // Projet dynamique = SEUL type de projets supporté actuellement
        $projet = $sp->creerProjet(Projet::PROJET_DYN);
        $version = $projet->getVersionDerniere();
        
        return $this->redirectToRoute('modifier_version', [ 'id' => $version->getIdVersion() ]);
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
        $sr                  = $this->sr;
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
            if ($versionActive != null)
            {
                $rallonges = $versionActive ->getRallonge();
                $cpt_rall  = count($rallonges->toArray());
            }
            else
            {
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
                //$loginnames = [];
                $loginnames = $su->collaborateurVersion2LoginNames();
            }

            $projets_resp[]   =
            [
                'projet'    => $projet,
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
                //$loginnames = [];
                $loginnames = $su->collaborateurVersion2LoginNames();
            }
            
            $projets_collab[] =
                [
                'projet'    => $projet,
                //'conso'     => $sp->getConsoCalculP($projet),
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
                'projets_collab' => $projets_collab,
                'projets_resp'   => $projets_resp,
                'projets_term'   => $projets_term,
                'menu'           => $menu,
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

        // LA SUITE DEPEND DU TYPE DE PROJET !
        // Affichage pour projets de type 4 (le seul type supporté actuellement))
        $type = $projet->getTypeProjet();
        switch ($type) {
            case Projet::PROJET_DYN:
                return $this->__consulter4($projet, $version, $request);
            default:
                $sj->errorMessage(__METHOD__ . " Type de projet inconnu: $type");
        }
    }


    // Consulter les projets de type 4 (=> projets dynamiques))
    private function __consulter4(Projet $projet, Version $version, Request $request)
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

        $version_form = Functions::createFormBuilder($ff, ['version' => $version ])
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
                    return $version->getNbVersion();
                }
            ]
        )
        ->add('submit', SubmitType::class, ['label' => 'Changer'])
        ->getForm();
        
        $version_form->handleRequest($request);

        if ($version_form->isSubmitted() && $version_form->isValid())
        {
            $version = $version_form->getData()['version'];
        }

        $menu = [];
        $menu[] = $sm->nouvelleRallonge($projet);
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
        
        $tmpl = 'projet/consulter_projet4.html.twig';

        $cv = $em->getRepository(CollaborateurVersion::class)
             ->findOneBy(['version' => $version, 'collaborateur' => $moi]);
        
        return $this->render(
            $tmpl,
            [
                'projet' => $projet,
                'version_form' => $version_form->createView(),
                'version' => $version,
                'session' => null,
                'menu' => $menu,
                'img_expose' => $img_expose,
                'img_justif_renou' => $img_justif_renou,
                'document' => $document,
                'cv' => $cv
            ]
        );
    }
}
