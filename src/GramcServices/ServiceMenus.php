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
 *  authors : Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

/***
 * class Menu = Permet d'afficher des liens html vers différents controleurs.
 *              Chaque fonction renvoit un tableau qui sera repris pour affichage par le twig
 *              Voir la macro menu dans macros.html.twig
 *
 * Clés du tableau menu:
 *      name             -> Nom du controleur symfony
 *      lien             -> Texte du lien html
 *      commentaire      -> Ce que fait le controleur en une phrase
 *      ok               -> Si true le lien est actif sinon le lien est inactif
 *      reason           -> Si le lien est inactif, explication du pourquoi. Pas utilisé si le lien est inactif
 *      todo (optionnel) -> Si le lien est actif, permet de visualiser le menu sous forme de todo liste - cf. consulter.html.twig, vers la ligne 20
 *
 *****************************************************************************************************************************************************/

 // ATTENTION - Certaines méthodes de cette classe sont INUTILISEES ACTUELLEMENT
 
namespace App\GramcServices;

use App\Entity\Session;
use App\Entity\Projet;
use App\Entity\Rallonge;
use App\Entity\Version;
use App\Entity\Individu;
use App\Entity\RapportActivite;
use App\GramcServices\Etat;
use App\GramcServices\Signal;
use App\Utils\Functions;
use App\GramcServices\Workflow\Session\SessionWorkflow;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\ORM\EntityManagerInterface;

class ServiceMenus
{
    private $token = null;
    
    public function __construct(
        private $max_rall,
        private ServiceVersions $sv,
        private ServiceProjets $sp,
        private ServiceJournal $sj,
        private GramcDate $grdt,
        private ValidatorInterface $sval,
        private ServiceSessions $ss,
        private TokenStorageInterface $tok,
        private AuthorizationCheckerInterface $ac,
        private EntityManagerInterface $em
    ) {
        $this->token = $this->tok->getToken();
    }

    /*****************************************************
     * Gestion de la priorité des boutons:
     *     - Si ok on fixe la priorité suivant le paramètre $priorite
     *     - Si pas ok ou pas spécifié on la met à BASSE
     *
     ******/

    public const BPRIO = 2; // Basse priorité il faudra cliquer sur un voir plus pour afficher
    public const HPRIO = 1; // Haute priorité on verra toujours le bouton
    
    private function __prio(array &$menu, int $priorite): void
    {
        if (isset($menu['ok']) && $menu['ok'] === true)
        {
            $menu['priorite'] = $priorite;
        }
        else
        {
            $menu['priorite'] = self::BPRIO;
        }
    }

    /*******************
     * Gestion des projets et des versions
     ***************************************************/

    public function nouveauProjet($type, int $priorite=self::HPRIO):array
    {
        $sj = $this->sj;
        switch ($type) {
            case Projet::PROJET_DYN:
                return $this->nouveauProjetDyn($priorite);
            default:
                $sj->throwException("Type de projet ($type) inconnu !");
        }
    }

    /*
     * Création d'un projet de type PROJET_DYN:
     *     - Peut être créé n'importe quand
     *     - Renouvelable
     *     - En standby au bout de 12 mois (voir le metaetat du projet)
     *     - Terminé 12 mois après le passage en standby si pas renouvelé entre temps
     *     - Créé seulement par un permanent, qui devient responsable du projet
     *
     */
    private function nouveauProjetDyn(int $priorite=self::HPRIO):array
    {
        $menu = [];

        $menu['commentaire'] = "Vous ne pouvez pas créer de nouveau projet dynamique";
        $menu['name'] = 'nouveau_projet';
        $menu['params'] = [ 'type' =>  Projet::PROJET_DYN ];
        $menu['lien'] = 'Nouveau projet dynamique';
        $menu['ok'] = false;

        if (! $this->peutCreerProjets())
        {
            $menu['raison'] = "Seuls les personnels permanents des laboratoires enregistrés peuvent créer un projet";
        }
        else
        {
            $menu['raison'] = '';
            $menu['commentaire'] = "Créez un nouveau projet, vous en serez le responsable";
            $menu['ok'] = true;
        }

        //$this->__prio($menu, $priorite);
        return $menu;
    }

    private function peutCreerProjets($user = null): bool
    {
        if ($user === null)
        {
            $user = $this->token->getUser();
        }

        if ($user != null && $user->peutCreerProjets())
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    //////////////////////////////////////

    // Menu principal Admin

    //////////////////////////////////////

    public function gererIndividu(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'individu_gerer';
        $menu['commentaire'] = "Gérer les utilisateurs de gramc";
        $menu['lien'] = "Utilisateurs";
        $menu['icone'] = "liste_utilisateur";

        if ($this->ac->isGranted('ROLE_ADMIN'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous n'êtes pas un administrateur";
        }
        $this->__prio($menu, $priorite);
        return $menu;
    }

    public function gererInvitations(int $priorite=self::HPRIO): array
    {
        $menu['name'] = 'invitations';
        $menu['commentaire'] = "Récapituler les invitations en cours";
        $menu['lien'] = "Invitations";
        $menu['icone'] = "mail";

        if ($this->ac->isGranted('ROLE_ADMIN'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous n'êtes pas un administrateur";
        }
        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////

    public function gererSessions(int $priorite=self::HPRIO): array
    {
        $menu['name'] = 'gerer_sessions';
        $menu['commentaire'] = "Gérer les sessions d'attribution";
        $menu['lien'] = "Sessions";
        $menu['icone'] = "sessions";


        if ($this->ac->isGranted('ROLE_ADMIN'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////

    public function bilanSession(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'bilan_session';
        $menu['commentaire'] = "Générer et télécharger le bilan de session";
        $menu['lien'] = "Bilan de session";
        $menu['icone'] = "bilan";


        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////

    public function bilanAnnuel(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'bilan_annuel';
        $menu['commentaire'] = "Générer et télécharger le bilan annuel";
        $menu['lien'] = "Bilan annuel";
        $menu['icone'] = "annee";


        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////

    public function projetsSession(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'projet_session';
        $menu['commentaire'] = "Gérer les projets par session";
        $menu['lien'] = "Projets ( par session )";
        $menu['icone'] = "projet_session";

        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////

    public function projetsAnnee(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'projet_annee';
        $menu['commentaire'] = "Gérer les projets par année";
        $menu['lien'] = "Projets ( par année )";
        $menu['icone'] = "annee";

        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////

    public function projet_donnees(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'projet_donnees';
        $menu['commentaire'] = "Projets ayant des demandes en stockage ou partage de données";
        $menu['lien'] = "Gestion et valo des données";
        $menu['icone'] = "donnees";

        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }
    //////////////////////////////////////

    public function projetsTous(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'projet_tous';
        $menu['commentaire'] = "Liste complète des projets";
        $menu['lien'] = "Tous les projets";
        $menu['icone'] = "tous";


        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    public function projetsDyn(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'projet_dynamique';
        $menu['commentaire'] = "Tous les projets dynamiques";
        $menu['lien'] = "Projets dynamiques";
        $menu['icone'] = "annee";


        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    public function rallongesDyn(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'rallonge_dynamique';
        $menu['commentaire'] = "Toutes les demandes d'extension de projets dynamiques";
        $menu['lien'] = "Demandes d'extension";
        $menu['icone'] = "annee";


        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur ou président pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////

    public function lireJournal(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'journal_list';
        $menu['commentaire'] = "Lire le journal des actions";
        $menu['lien'] = "Lire le journal";
        $menu['icone'] = "lire_journal";

        if ($this->ac->isGranted('ROLE_ADMIN'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////

    public function phpInfo(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'phpinfo';
        $menu['commentaire'] = "Infos sur php et sur aussi sur gramc";
        $menu['lien'] = "infos techniques";
        $menu['icone'] = "technique";

        if ($this->ac->isGranted('ROLE_ADMIN'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être un administrateur pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }
    //////////////////////////////////////

    public function gererLaboratoires(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'gerer_laboratoires';
        $menu['commentaire'] = "Gérer la liste des laboratoires enregistrés";
        $menu['lien'] = "Laboratoires";
        $menu['icone'] = "laboratoire";

        if ($this->ac->isGranted('ROLE_OBS'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être au moins un observateur pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    public function gererFormations(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'gerer_formations';
        $menu['commentaire']=   "Gérer la liste des formations";
        $menu['lien'] = "Formations";
        $menu['icone'] = "formation";

        if ($this->ac->isGranted('ROLE_OBS'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être au moins un observateur pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    public function gererServeurs(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'gerer_serveurs';
        $menu['commentaire']=   "Gérer la liste des serveurs";
        $menu['lien'] = "Serveurs";
        $menu['icone'] = "indefinit";

        if ($this->ac->isGranted('ROLE_OBS'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être au moins un observateur pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    public function gererResources(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'gerer_ressources';
        $menu['commentaire']=   "Gérer la liste des ressources";
        $menu['lien'] = "Ressources";
        $menu['icone'] = "indefinit";

        if ($this->ac->isGranted('ROLE_OBS'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être au moins un observateur pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////

    public function gererThematiques(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'gerer_thematiques';
        $menu['commentaire'] = "Gérer la liste des thématiques";
        $menu['lien'] = "Thématiques";
        $menu['icone'] = "thematique";

        if ($this->ac->isGranted('ROLE_OBS'))
        {
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['raison'] = "Vous devez être au moins un observateur pour accéder à cette page";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////

    // Menu principal Projet

    //////////////////////////////////////

    public function changerResponsable(Version $version, int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'changer_responsable';
        $menu['param'] = $version->getIdVersion();
        $menu['lien'] = "Nouveau responsable";
        $user = $this->token->getUser();

        if ($this->ac->isGranted('ROLE_ADMIN'))
        {
            $menu['commentaire'] = "Changer le responsable du projet en tant qu'administrateur";
            $menu['raison'] = "L'admininstrateur peut TOUJOURS modifier le responsable d'une version quelque soit son état !";
            $menu['ok'] = true;
        }
        else
        {
            $menu['ok'] = false;
            $menu['commentaire'] = "Vous ne pouvez pas changer le responsable de ce projet";
    
            $etatVersion = $version->getEtatVersion();
    
            if ($version->getEtatVersion() != Etat::EDITION_DEMANDE)
            {
                $menu['raison'] = "Commencez par demander le renouvellement du projet !";
            }
            elseif ($etatVersion === Etat::EDITION_EXPERTISE || $etatVersion == Etat::EXPERTISE_TEST)
            {
                $menu['raison'] = "Le projet a déjà été envoyé en expertise";
            }
            elseif ($etatVersion !== Etat::EDITION_DEMANDE && $etatVersion != Etat::EDITION_TEST)
            {
                $menu['raison'] = "Cette version de projet n'est pas en mode édition";
            }
            elseif (! $version->isResponsable($user))
            {
                $menu['raison'] = "Seul le responsable du projet peut passer la main. S'il n'est pas joignable, merci de nous envoyer un mail";
            }
            else
            {
                $menu['ok'] = true;
                $menu['commentaire'] = "Quitter la responsabilité de ce projet";
            }
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    ////////////////////////////////////

    public function modifierVersion(Version $version, int $priorite=self::HPRIO):array
    {
        $sv = $this->sv;
        
        $menu['name'] = 'modifier_version';
        $menu['param'] = $version->getIdVersion();
        $menu['lien'] = "Modifier";
        $menu['icone'] = "modifier";
        $menu['commentaire'] = "Vous ne pouvez pas modifier ce projet";

        $menu['ok'] = false;

        if ($this->ac->isGranted('ROLE_ADMIN'))
        {
            $menu['commentaire'] = "Modifier le projet en tant qu'administrateur";
            $menu['raison'] = "L'administrateur peut TOUJOURS modifier le projet quelque soit son état !";
            $menu['ok'] = true;
            if ($sv->validateVersion($version))
            {
                $menu['todo'] = "Compléter le formulaire";
            }
        }
        else
        {
            $etatVersion = $version->getEtatVersion();
            if ($etatVersion === Etat::EDITION_EXPERTISE)
            {
                $menu['raison'] = "Le projet a déjà été envoyé en expertise !";
            }
            elseif ($version->isCollaborateur($this->token->getUser()) === false)
            {
                $menu['raison'] = "Seul un collaborateur du projet peut modifier ou supprimer le projet";
            }
            elseif ($etatVersion !=  Etat::EDITION_DEMANDE)
            {
                $menu['raison'] = "Le projet n'est pas en mode d'édition";
            }
            else
            {
                $menu['ok'] = true;
                $menu['commentaire'] = "Modifier votre demande de ressources";
                $menu['todo'] = "<strong>Vérifier</strong> le projet et le <strong>compléter</strong> si nécessaire";
            }
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    ///////////////////////////////////////////////////////////

    public function modifierCollaborateurs(Version $version, int $priorite=self::HPRIO):array
    {
        $user = $this->token->getUser();

        $menu['name'] = 'modifier_collaborateurs';
        $menu['param'] = $version->getIdVersion();
        $menu['lien'] = "Collaborateurs";
        $menu['priorite'] = $priorite;

        if ($this->ac->isGranted('ROLE_ADMIN'))
        {
            $menu['commentaire'] = "Modifier les collaborateurs en tant qu'administrateur";
            $menu['ok'] = true;
        }
        elseif (! $version->isResponsable($user))
        {
            $menu['ok'] = false;
            $menu['commentaire'] = 'Bouton inactif';
            $menu['raison'] = "Seul le responsable du projet peut ajouter ou supprimer des collaborateurs";
        }
        elseif ($version->getEtat() === Etat::TERMINE || $version->getEtat() === Etat::ANNULE)
        {
            $menu['ok'] = false;
            $menu['commentaire'] = 'Bouton inactif';
            $menu['raison'] = "Cette version est terminée !";
        }
        else
        {
            $menu['ok'] = true;
            $menu['commentaire'] = "Modifier la liste des collaborateurs du projet";
        }
        
        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////////////////////////////////

    public function televerserRapportAnnee(Version $version, int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'televerser_rapport_annee';
        $menu['ok'] = false;
        $menu['priorite'] = $priorite;

        if ($version != null)
        {
            $etat = $version->getEtatVersion();
            $menu['param'] = $version->getIdVersion();
            $menu['lien'] = "Rapport d'activité de l'année " . $version->getAnneeSession();

            if ($this->ac->isGranted('ROLE_ADMIN') && ($etat === Etat::ACTIF || $etat === Etat::TERMINE)) {
                $menu['commentaire'] = "Téléverser un rapport d'activité pour un projet en tant qu'administrateur";
                $menu['raison'] = "L'administrateur peut TOUJOURS téléverser un rapport d'activité pour un projet !";
                $menu['ok'] = true;
            }
            else
            {
                $menu['ok'] = false;
                $menu['commentaire'] = "Vous ne pouvez pas téléverser un rapport d'activité pour ce projet";
    
                if ($version->getProjet() != null) {
                    $rapportActivite = $this->em->getRepository(RapportActivite::class)->findOneBy(
                        [
                        'projet' => $version->getProjet(),
                        'annee' => $version->getAnneeSession(),
                        ]
                    );
                } else {
                    $rapportActivite = null;
                    $this->sj->errorMessage(__METHOD__ . ":" . __LINE__ . " version " . $version . " n'est pas associée à aucun projet !");
                }
    
                //if( $etat != Etat::ACTIF && $etat != Etat::TERMINE)
                //		$menu['raison'] = "Vous devez soumettre le rapport annuel quand vous avez fini vos calculs de l'année en question";
                if (! $version->isCollaborateur($this->token->getUser())) {
                    $menu['raison'] = "Seul un collaborateur du projet peut téléverser un rapport d'activité pour un projet";
                }
                //elseif( $rapportActivite != null)
                //     $menu['raison'] = "Vous avez déjà téléversé un rapport d'activité pour ce projet pour l'année en question";
                else
                {
                    $menu['ok'] = true;
                    $menu['commentaire'] = "Téléverser votre rapport d'activité pour l'année " . $version->getAnneeSession() . "si vous avez déjà terminé vos calculs";
                    $menu['todo'] = "Téléverser votre rapport d'activité pour " . $version->getAnneeSession();
                }
            }
        } else {
            $menu['param'] = 0;
            $menu['lien'] = "Rapport d'activité";
            $menu['commentaire'] = "Vous ne pouvez pas téléverser un rapport d'activité pour ce projet";
            $menu['raison'] = "Mauvaise version du projet !";
            $this->sj->errorMessage(__METHOD__ . ':' . __LINE__ . " Version null !");
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    ////////////////////////////////////////////////////////////
    public function telechargerModeleRapportDactivite(Version $version, int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'telecharger_modele';
        $menu['lien'] = "Modèle de rapport d'activité";
        $menu['ok'] = false;
        $menu['commentaire'] = "Vous ne pouvez pas télécharger un modèle de rapport d'activité pour ce projet";
        $menu['telecharger'] = "telecharger";
        $menu['priorite'] = $priorite;

        if ($version != null)
        {
            if ($this->ac->isGranted('ROLE_ADMIN')) {
                $menu['commentaire'] = "Télécharger un modèle de rapport d'activité en tant qu'administrateur";
                $menu['raison'] = "L'admininstrateur peut TOUJOURS télécharger un modèle de rapport d'activité !";
                $menu['ok'] = true;
            }
            else
            {
                $etat = $version->getEtatVersion();
    
                if (! $version->isCollaborateur($this->token->getUser())) {
                    $menu['raison'] = "Seul un collaborateur du projet peut télécharger un modèle de rapport d'activité pour ce projet";
                }
                //elseif( $etat != Etat::ACTIF && $etat != Etat::TERMINE)
                //    $menu['raison'] = "Vous devez soumettre le rapport annuel quand vous avez fini vos calculs de l'année en question";
                else {
                    $menu['ok'] = true;
                    $menu['commentaire'] = "Télécharger un modèle de rapport d'activité";
                }
            }
        }
        else
        {
            $menu['raison'] = "Mauvaise version du projet !";
            $this->sj->errorMessage(__METHOD__ . ':' . __LINE__ . " Version null !");
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    ////////////////////////////////////////////////////////////

    public function gererPublications(Projet $projet, int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'gerer_publications';
        $menu['param'] = $projet->getIdProjet();
        $menu['lien'] = "Publications";
        $menu['priorite'] = $priorite;

        if ($this->ac->isGranted('ROLE_ADMIN')) {
            $menu['commentaire'] = "Modifier les publications en tant qu'administrateur";
            $menu['raison'] = "L'admininstrateur peut TOUJOURS modifier les publications du projet  !";
            $menu['ok'] = true;
        }
        else
        {
            $version = $projet->derniereVersion();
            $etat = $version->getEtatVersion();
    
            $menu['ok'] = false;
            $menu['commentaire'] = "Vous ne pouvez pas modifier les publications";
    
            if (! $projet->isCollaborateur($this->token->getUser())) {
                $menu['raison'] = "Seul un collaborateur du projet peut gérer les publicatins associées à un projet";
            } elseif ($this->sv->isNouvelle($version) && ! ($etat === Etat::ACTIF || $etat === Etat::TERMINE)) {
                $menu['raison'] = "Vous ne pouvez ajouter que des publications que vous avez publiées grâce au calcul sur notre mésocentre";
            } else {
                $menu['ok'] = true;
                $menu['commentaire'] = "Gérer les publicatins associées au projet " . $projet->getIdProjet();
                $menu['todo'] = '<strong>Signaler les dernières publications</strong> dans lesquelles le mésocentre a été remercié pour ce projet';
            }
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    /////////////////////////////////////////////////////////////////////
    public function renouvelerVersion(Version $version, int $priorite=self::BPRIO):array
    {
        return $this->__renouvelerVersionDyn($version, $priorite);
    }
    
    private function __renouvelerVersionDyn(Version $version, int $priorite=self::BPRIO):array
    {
        $menu['name'] = 'renouveler_version';
        $menu['param'] = $version->getIdVersion();
        $menu['lien'] = "Renouveler";
        $menu['icone'] = "renouveler";
        $menu['commentaire'] = "Vous ne pouvez pas demander de renouvellement";
        $menu['ok'] = false;

        // On travaille ici sur la dernière version du projet !
        $projet = $version->getProjet();
        $verder = $projet->getVersionDerniere();

        // Pour l'instant on supprime la possibilité de créer une nouvelle version n'importe quand
        // c-à-d tant qu'on n'est pas en état ACTIF_R (<30 j avant la date de fin)
        // ou TERMINE (<365j APRES la date de fin)
        //if ($verder->getEtatVersion() != Etat::ACTIF && $verder->getEtatVersion() != Etat::ACTIF_R && $verder->getEtatVersion() != Etat::TERMINE )
        if ($verder->getEtatVersion() != Etat::ACTIF_R && $verder->getEtatVersion() != Etat::TERMINE )
        {
            $menu['raison'] = "Pas possible de créer une nouvelle version pour l'instant";
        }
        elseif ($verder->getProjet()->getEtatProjet() === Etat::TERMINE)
        {
            $menu['raison'] = "Votre projet est terminé";
        }
        elseif ($verder->isCollaborateur($this->token->getUser()))
        {
            $menu['commentaire'] = "Demander de nouvelles ressources sur ce projet";
            $priorite = self::HPRIO;
            $menu['ok'] = true;
        }
        elseif ($this->ac->isGranted('ROLE_ADMIN'))
        {
            $menu['commentaire'] = "Demander de nouvelles ressources sur ce projet en tant qu'administrateur";
            $priorite = self::HPRIO;
            $menu['ok'] = true;
        }
        else
        {
            $menu['raison'] = "Vous n'avez pas le droit de demander une nouvelle version, vous n'êtes pas collaborateur";
        }
        $this->__prio($menu, $priorite);
        return $menu;
    }


    public function envoyerEnExpertise(Version $version, int $priorite=self::HPRIO):array
    {
        if ($version === null) {
            return [];
        }

        $type = $version->getTypeVersion();
        switch ($type) {
            case Projet::PROJET_DYN:
                return $this->__envoyerVersion4($version, $priorite);
            default:
                $sj->errorMessage(__METHOD__ . " Type de version inconnu: $type");
        }
    }

    // Envoyer en expertise pour un projet de type 4
    private function __envoyerVersion4(Version $version, int $priorite):array
    {
        $projet = $version -> getProjet();
        $user = $this->token->getUser();

        $menu['name'] = 'envoyer_en_expertise';
        $menu['param'] = $version->getIdVersion();
        $menu['lien'] = "Envoyer en validation";
        $menu['icone'] = "envoyer";
        $menu['commentaire'] = "Vous ne pouvez pas envoyer ce projet en validation";
        $menu['ok'] = false;
        $menu['raison'] = "";
        $menu['incomplet'] = false;

        $etatVersion = $version->getEtatVersion();

        if ($version->isResponsable($user) === false)
        {
            $menu['raison'] = "Seul le responsable du projet peut envoyer ce projet en validation";
            $this->__prio($menu, $priorite);
            return $menu;
        }

        if ($etatVersion !=  Etat::EDITION_DEMANDE)
        {
            $menu['raison'] = "Le projet n'est plus en édition !";
            $this->__prio($menu, $priorite);
            return $menu;
        }

        $menu['ok'] = true;
        $menu['commentaire'] = "Envoyer votre demande pour validation. ATTENTION, vous ne pourrez plus la modifier par la suite";
        $menu['todo'] = "Envoyer le projet en <strong>validation</strong>";
        $menu['name'] = 'avant_modifier_version';
        $this->__prio($menu, $priorite);
        return $menu;
    }

    ////////////////////////////////////////////////////////////////////////////

    public function testerMail(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'mail_tester';
        $menu['lien'] = "Tester le système de mail";
        $menu['commentaire'] = "Vous ne pouvez pas tester le mail";
        $menu['ok'] = false;
        $menu['raison'] = "Vous n'êtes pas un administrateur";
        $menu['icone'] = "mail";

        if ($this->ac->isGranted('ROLE_ADMIN')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Test du mail";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    ////////////////////////////////////////////////////////////////////////////

    public function tempsAvancer(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'param_avancer';
        $menu['lien'] = "Avancer dans le temps";
        $menu['commentaire'] = "Vous ne pouvez pas avancer dans le temps";
        $menu['ok'] = false;
        $menu['raison'] = "Vous n'êtes pas un administrateur";
        $menu['icone'] = "avancer_temps";

        if ($this->ac->isGranted('ROLE_ADMIN')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Vous pouvez avancer dans le temps (pour déboguage)";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    ////////////////////////////////////////////////////////////////////////////

    //public function mailToResponsablesRallonge(int $priorite=self::HPRIO):array
    //{
        //$session = $this->ss->getSessionCourante();
        //if ($session != null) {
            //$etatSession     = $session->getEtatSession();
            //$idSession       = $session->getIdSession();
        //} else {
            //$this->sj->errorMessage(__METHOD__ . ':' . __LINE__ . " La session courante est nulle !");
            //$etatSession     = null;
            //$idSession       = 'X';
        //}

        //$menu['name']        = 'mail_to_responsables_rallonge';
        //$menu['param']       = $idSession;
        //$menu['lien']        = "Mail - Proposition de rallonge";
        //$menu['commentaire'] = "Vous ne pouvez pas envoyer un mail aux responsables de projets";
        //$menu['ok']          = false;
        //$menu['raison']      = "Vous n'êtes pas un administrateur ou président";
        //$menu['icone']      = "mail";

        //if ($this->ac->isGranted('ROLE_ADMIN') || $this->ac->isGranted('ROLE_PRESIDENT')) {
            //$menu['ok']          = true;
            //$menu['commentaire'] = "Envoyer un rappel aux responsables des projets qui n'ont pas renouvelé !";
        //}

        //$this->__prio($menu, $priorite);
        //return $menu;
    //}

    ////////////////////////////////////////////////////////////////////////////

    //public function mailToResponsablesFiche(int $priorite=self::HPRIO):array
    //{
        //$session = $this->ss->getSessionCourante();
        //if ($session != null) {
            //$etatSession     = $session->getEtatSession();
            //$idSession       = $session->getIdSession();
        //} else {
            //$this->sj->errorMessage(__METHOD__ . ':' . __LINE__ . " La session courante est nulle !");
            //$etatSession     = null;
            //$idSession       = 'X';
        //}

        //$menu['name']        = 'mail_to_responsables_fiche';
        //$menu['param']       = $idSession;
        //$menu['lien']        = "Mail - projets sans fiche";
        //$menu['commentaire'] = "Vous ne pouvez pas envoyer un mail aux responsables des projets qui n'ont pas téléversé leur fiche projet";
        //$menu['ok']          = false;
        //$menu['raison']      = "Vous n'êtes pas un administrateur ou président";
        //$menu['icone']      = "mail";

        //if ($etatSession    !=  Etat::ACTIF && $etatSession    !=  Etat::EN_ATTENTE) {
            //$menu['raison'] = "La session n'est pas en mode actif ou en attente";
        //} elseif ($this->ac->isGranted('ROLE_ADMIN') || $this->ac->isGranted('ROLE_PRESIDENT')) {
            //$menu['ok']          = true;
            //$menu['commentaire'] = "Envoyer un rappel aux responsables des projets qui n'ont pas téléversé leur fiche projet !";
        //}

        //$this->__prio($menu, $priorite);
        //return $menu;
    //}

    ////////////////////////////////////////////////////////////////////////////

    public function nettoyerRgpd(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'rgpd';
        $menu['lien'] = "Nettoyage pour conformité au RGPD";
        $menu['commentaire'] = "Vous ne pouvez pas supprimer les projets ou les utilisateurs anciens";
        $menu['ok'] = false;
        $menu['raison'] = "Vous n'êtes pas un administrateur";
        $menu['icone'] = "nettoyage";

        if ($this->ac->isGranted('ROLE_ADMIN')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Suppresion des anciens projets et des utilisateurs orphelins";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    /////////////////////////////////////////////////////////////////////////////////

    public function afficherConnexions(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'connexions';
        $menu['lien'] = "Personnes connectées";
        $menu['commentaire'] = "Vous ne pouvez pas voir les personnes connectées";
        $menu['ok'] = false;
        $menu['raison'] = "Vous n'êtes pas un administrateur";
        $menu['icone'] = "personnes_connectees";

        if ($this->ac->isGranted('ROLE_ADMIN')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Vous pouvez pouvez voir les personnes connectées";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    ////////////////////////////////////////////////////////////////////////////

    public function nouvelleRallonge(Projet $projet, int $priorite=self::HPRIO):array
    {
        $sp = $this->sp;

        $menu['lien'] = "Extension";
        $menu['commentaire'] = "Vous ne pouvez pas créer une nouvelle extension";
        $menu['ok'] = false;

        $version = $this->sp->versionActive($projet);
        $max_rall= $this->max_rall;
        $rallonges = null;
        $rallonges = $this->em->getRepository(Rallonge::class)->findRallongesOuvertes($version);

        // S'il y a une rallonge en cours de traitement on renvoie l'utilisateur dessus !'

        if ($version === null)
        {
            $menu['name'] = 'nouvelle_rallonge';
            $menu['raison'] = "Le projet " . $projet . " n'est pas actif !";
            $menu['param'] = $projet->getIdProjet();;
        }
        elseif (!empty($rallonges))
        {
            $menu['ok'] = true;
            $menu['name'] = 'consulter_rallonge';
            $menu['param'] = $rallonges[0]->getIdRallonge();
            $menu['commentaire'] = "Aller vers la demande d'extension en cours de traitement";
            
        }
        elseif (count($version->getRallonge()) >= $max_rall)
        {
            $menu['name'] = 'nouvelle_rallonge';
            $menu['param'] = $projet->getIdProjet();;
            $menu['raison'] = "Pas plus de $max_rall demandes d'extension par an !";
        }
        else
        {
            $menu['ok'] = true;
            $menu['name'] = 'nouvelle_rallonge';
            $menu['param'] = $projet->getIdProjet();;
            $menu['commentaire'] = "Extension (ou demande au fil de l'eau, ou rallonge): Demander des ressources supplémentaires sans modifier la date de fin du projet";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    ////////////////////////////////////////////////////////////////////////////

    public function modifierRallonge(Rallonge $rallonge, int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'modifier_rallonge';
        $menu['param'] = $rallonge->getIdRallonge();
        $menu['lien'] = "Modifier";
        $menu['icone'] = "modifier";
        $menu['commentaire'] = "Vous ne pouvez pas modifier la demande";
        $menu['ok'] = false;
        $menu['raison'] = "raison inconnue";


        $version = $rallonge->getVersion();
        if ($version != null) {
            $projet = $version->getProjet();
        } else {
            $projet = null;
        }

        if ($version === null) {
            $menu['raison'] = "Cette demande n'est associée à aucun projet !";
        }
        //elseif( $version->getEtatVersion()  === Etat::NOUVELLE_VERSION_DEMANDEE )
        //    $menu['raison'] = "Un renouvellement du projet " . $projet . " est déjà accepté !";
        elseif ($version->getProjet() === null) {
            $menu['raison'] = "Cette version du projet n'est associée à aucun projet";
        } elseif ($version->getProjet()->getEtatProjet() === Etat::TERMINE) {
            $menu['raison'] = "Votre projet est ou sera prochainement terminé";
        } elseif ($version->getEtatVersion() === Etat::ANNULE) {
            $menu['raison'] = "Votre projet est annulé";
        } elseif ($version->getEtatVersion() === Etat::TERMINE) {
            $menu['raison'] = "Votre projet est déjà terminé";
        } elseif ($rallonge->getEtatRallonge() === Etat::ANNULE) {
            $menu['raison'] = "Cette demande a été annulée";
        } elseif ($rallonge->getEtatRallonge() !== Etat::EDITION_DEMANDE) {
            $menu['raison'] = "Cette demande a déjà été envoyée en validation";
        } elseif ($this->ac->isGranted('ROLE_ADMIN')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Vous pouvez modifier la demande en tant qu'administrateur !";
        } elseif ($version->isCollaborateur($this->token->getUser())) {
            $menu['commentaire'] = "Vous pouvez modifier votre demande " ;
            $menu['ok'] = true;
        } else {
            $menu['raison'] = "Vous n'avez pas le droit de modifier cette demande, vous n'êtes pas un collaborateur";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }


    ////////////////////////////////////////////////////////////////////////////

    public function envoyerEnExpertiseRallonge(Rallonge $rallonge, int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'avant_envoyer_rallonge';
        $menu['param'] = $rallonge->getIdRallonge();
        $menu['icone'] = 'envoyer';
        $menu['lien'] = "Envoyer en validation";
        $menu['commentaire'] = "Vous ne pouvez pas envoyer cette demande d'extension en validation";
        $menu['ok'] = false;
        $menu['raison'] = "raison inconnue";
        $user = $this->token->getUser();


        $version = $rallonge->getVersion();
        if ($version != null) {
            $projet = $version->getProjet();
        } else {
            $projet = null;
        }

        if ($version === null) {
            $menu['raison'] = "Cette demande n'est associée à aucun projet !";
        }
        elseif ($version->getProjet() === null) {
            $menu['raison'] = "Cette version du projet n'est associée à aucun projet";
        } elseif ($version->getProjet()->getEtatProjet() === Etat::TERMINE) {
            $menu['raison'] = "Votre projet est ou sera prochainement terminé";
        } elseif ($version->getEtatVersion() === Etat::ANNULE) {
            $menu['raison'] = "Votre projet est annulé";
        } elseif ($version->getEtatVersion() === Etat::TERMINE) {
            $menu['raison'] = "Votre projet de est déjà terminé";
        } elseif ($rallonge->getEtatRallonge() === Etat::ANNULE) {
            $menu['raison'] = "Cette demande a été annulée";
        } elseif ($rallonge->getEtatRallonge() !== Etat::EDITION_DEMANDE) {
            $menu['raison'] = "Cette demande a déjà été envoyée en validation";
        } elseif ($this->ac->isGranted('ROLE_ADMIN')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Vous pouvez envoyer cette demande en validation en tant qu'administrateur !";
        } elseif ($version->isResponsable($user)) {
            $menu['commentaire'] = "Vous pouvez envoyer votre demande en validation" ;
            $menu['ok'] = true;
        } else {
            $menu['raison'] = "Vous n'avez pas le droit d'envoyer cette demande en validation, vous n'êtes pas le responsable du projet";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    ////////////////////////////////////////////////////////////////////////////

    public function televersementGenerique(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'televersement_generique';
        $menu['lien'] = "Téléversements";
        $menu['commentaire'] = "Téléverser des fiches projet ou des rapports d'activité";
        $menu['ok'] = false;
        $menu['raison'] = "Vous n'êtes pas un administrateur";
        $menu['icone'] = "televersement_generique";

        if ($this->ac->isGranted('ROLE_ADMIN')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Téléverser des fiches projet ou des rapports d'activité";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////////////////////////////////////////////


    public function telechargerFiche(Version $version, int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'version_fiche_pdf';
        $menu['param'] = $version->getIdVersion();
        $menu['lien'] = "Fiche projet";
        $menu['commentaire'] = "Vous ne pouvez pas télécharger la fiche de ce projet";
        $menu['ok'] = false;
        $menu['raison'] = "Vous n'êtes pas un collaborateur du projet";
        $menu['icone'] = "telecharger";
        $menu['priorite'] = $priorite;

        if ($this->ac->isGranted('ROLE_ADMIN')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Télécharger la fiche projet en tant qu'administrateur";
        } elseif (! $version->isCollaborateur($this->token->getUser())) {
            $menu['raison'] = "Vous n'êtes pas un collaborateur du projet";
        } elseif (($this->sv->isSigne($version) === true)) {
            $menu['raison'] = "La fiche projet signée a déjà été téléversée";
        } else {
            $menu['ok'] = true;
            $menu['commentaire'] = "Télécharger la fiche projet";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////////////////////////////////////////////
    public function televerserFiche(Version $version, int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'version_televerser_fiche';
        //$menu['name'] = 'televerser';
        //$menu['param'] = $version->getIdVersion();
        $menu['params'] = [ 'id' => $version->getIdVersion(), 'filename' => 'fiche.pdf' ];
        $menu['lien'] = "Fiche projet";
        $menu['commentaire'] = "Vous ne pouvez pas téléverser la fiche de ce projet";
        $menu['ok'] = false;
        $menu['raison'] = "Vous n'êtes pas un collaborateur du projet";
        $menu['priorite'] = $priorite;
        $menu['icone'] = "televerser";

        if ($this->ac->isGranted('ROLE_ADMIN')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Téléverser la fiche projet en tant qu'administrateur";
        } elseif (! $version->isCollaborateur($this->token->getUser())) {
            $menu['raison'] = "Vous n'êtes pas un collaborateur du projet";
        } elseif ($this->sv->isSigne($version) === true) {
            $menu['raison'] = "La fiche projet signée a déjà été téléversée";
        } else {
            $menu['ok'] = true;
            $menu['commentaire'] = "Téléverser la fiche projet";
            $menu['todo'] = "Télécharger la fiche projet, la faire signer et la téléverser";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public function statistiquesEtablissement(int $priorite=self::HPRIO): array
    {
        $menu['name'] = 'statistiques_etablissement';
        $menu['lien'] = "Etablissements";

        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Vous pouvez accéder aux statistiques par établissement !";
        } else {
            $menu['ok'] = false;
            $menu['commentaire'] = "Vous ne pouvez pas accéder aux statistiques par établissement !";
            $menu['raison'] = "Vous devez être président ou administrateur pour y accéder";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public function statistiquesLaboratoire(int $priorite=self::HPRIO): array
    {
        $menu['name'] = 'statistiques_laboratoire';
        $menu['lien'] = "Laboratoires";

        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Vous pouvez accéder aux statistiques par laboratoire !";
        } else {
            $menu['ok'] = false;
            $menu['commentaire'] = "Vous ne pouvez pas accéder aux statistiques par laboratoire !";
            $menu['raison'] = "Vous devez être président ou administrateur pour y accéder";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public function statistiquesThematique(int $priorite=self::HPRIO): array
    {
        $menu['name'] = 'statistiques_thematique';
        $menu['lien'] = "Thématiques";

        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Vous pouvez accéder aux statistiques par thématique !";
        } else {
            $menu['ok'] = false;
            $menu['commentaire'] = "Vous ne pouvez pas accéder aux statistiques par thématique !";
            $menu['raison'] = "Vous devez être président ou administrateur pour y accéder";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public function statistiques(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'statistiques';
        $menu['lien'] = "Statistiques";
        $menu['icone'] = "statistiques";

        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Vous pouvez accéder aux statistiques  !";
        } else {
            $menu['ok'] = false;
            $menu['commentaire'] = "Vous ne pouvez pas accéder aux statistiques  !";
            $menu['raison'] = "Vous devez être président ou observateur";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    public function statistiquesDyn(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'statistiques_dyn';
        $menu['lien'] = "Stats prj dynamiques";
        $menu['icone'] = "statistiques";

        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Vous pouvez accéder aux statistiques  !";
        } else {
            $menu['ok'] = false;
            $menu['commentaire'] = "Vous ne pouvez pas accéder aux statistiques  !";
            $menu['raison'] = "Vous devez être au moins observateur";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    public function statistiquesFormation(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'statistiques_formation';
        $menu['lien'] = "Demandes de formation";
        $menu['icone'] = "formation";

        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Vous pouvez accéder aux statistiques  !";
        } else {
            $menu['ok'] = false;
            $menu['commentaire'] = "Vous ne pouvez pas accéder aux statistiques  !";
            $menu['raison'] = "Vous devez être au moins observateur";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public function statistiquesCollaborateur(int $priorite=self::HPRIO): array
    {
        $menu['name'] = 'statistiques_collaborateur';
        $menu['lien'] = "Collaborateurs";

        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Vous pouvez accéder aux statistiques concernant les collaborateurs !";
        } else {
            $menu['ok'] = false;
            $menu['commentaire'] = "Vous ne pouvez pas accéder aux  statistiques concenrant les collaborateurs!";
            $menu['raison'] = "Vous devez être président ou administrateur pour y accéder";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public function statistiquesRepartition(int $priorite=self::HPRIO): array
    {
        $menu['name'] = 'statistiques_repartition';
        $menu['lien'] = "Projets";

        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Vous pouvez accéder aux statistiques concernant la répartition des projets !";
        } else {
            $menu['ok'] = false;
            $menu['commentaire'] = "Vous ne pouvez pas accéder aux  statistiques concenrant la répartition des projets !";
            $menu['raison'] = "Vous devez être président ou administrateur pour y accéder";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    //////////////////////////////////////////////////////////////////////////

    public function publicationsAnnee(int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'publication_annee';
        $menu['lien'] = "Publications";
        $menu['icone'] = "publications";

        if ($this->ac->isGranted('ROLE_OBS') || $this->ac->isGranted('ROLE_PRESIDENT')) {
            $menu['ok'] = true;
            $menu['commentaire'] = "Liste des publications par année";
        } else {
            $menu['ok'] = false;
            $menu['commentaire'] = "Vous ne pouvez pas accéder aux publications  !";
            $menu['raison'] = "Vous devez être président ou observateur";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }

    /*
     * Demandes concernant stockage et partage des données
     */
    public function donnees(Version $version, int $priorite=self::HPRIO):array
    {
        $menu['name'] = 'donnees';
        $menu['param'] = $version->getIdVersion();
        $menu['lien'] = "Vos données";
        $menu['priorite'] = $priorite;
        $user = $this->token->getUser();

        if ($this->ac->isGranted('ROLE_ADMIN')) {
            $menu['commentaire'] = "Gestion et valorisation des données en tant qu'admin";
            $menu['ok'] = true;
        } elseif (! $version->isResponsable($user)) {
            $menu['ok'] = false;
            $menu['commentaire'] = "Bouton inactif";
            $menu['raison'] = "Vous n'êtes pas responsable du projet";
        } elseif ($version->getEtat() === Etat::TERMINE || $version->getEtat() === Etat::ANNULE) {
            $menu['ok'] = false;
            $menu['commentaire'] = 'Bouton inactif';
            $menu['raison'] = "Cette version est terminée !";
        } else {
            $menu['ok'] = true;
            $menu['commentaire'] = "Gestion et valorisation des données";
        }

        $this->__prio($menu, $priorite);
        return $menu;
    }
}
