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
 *  authors : Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace App\GramcServices;

use App\Entity\CollaborateurVersion;
use App\Entity\Expertise;
use App\Entity\Individu;
use App\Entity\Projet;
use App\Entity\RapportActivite;
// Pour la suppression des projets RGPD
use App\Entity\Session;
use App\Entity\Sso;
use App\Entity\Version;
// use App\GramcServices\ServiceJournal;

// use Symfony\Bridge\Monolog\Logger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ServiceProjets
{
    private $token;

    public function __construct(
        private $prj_prefix,
        private $signature_directory,
        private $rapport_directory,
        private $fig_directory,
        private $dfct_directory,
        private GramcDate $grdt,
        private ServiceVersions $sv,
        private ServiceSessions $ss,
        private ServiceDacs $sdac,
        private ServiceJournal $sj,
        private ServiceRessources $sroc,
        private LoggerInterface $log,
        private AuthorizationCheckerInterface $sac,
        private TokenStorageInterface $tok,
        private EntityManagerInterface $em
    ) {
        $this->token = $tok->getToken();
    }

    /****************
     * Création d'un nouveau projet, c'est-à-dire:
     *    - Création du projet
     *    - Création d'une première version
     *
     * params: Le type de projet
     * Retourne: Le nouveau projet
     *
     ************************************************/
    public function creerProjet(int $type): Projet
    {
        $sv = $this->sv;
        $grdt = $this->grdt;
        $em = $this->em;

        // Création du projet
        $annee = $grdt->format('y');

        $projet = new Projet($type);
        $projet->setIdProjet($this->nextProjetId($annee, $type));
        $projet->setEtatProjet(Etat::RENOUVELABLE);
        $projet->setTetatProjet(Etat::STANDBY);

        // Ecriture du projet dans la BD
        $em->persist($projet);
        $em->flush();

        // Création de la première version
        $version = $sv->creerVersion($projet);

        return $projet;
    }

    /****************
     * Suppression d'un projet, en commençant par supprimer les objets User
     *
     * Params: $p Le projet à supprimer
     ************************************************/
    private function __supprimerProjet(Projet $projet): void
    {
        $em = $this->em;

        // Supprimer les User
        $users = $projet->getUser();
        foreach ($users as $u) {
            $projet->removeUser($u);
            $em->remove($u);
        }
        $em->flush();

        // Supprimer le projet
        $em->remove($projet);
        $em->flush();
    }

    /****************
     * Suppression d'une version
     *    - Suppression des Dac associés
     *    - Suppression de la version
     *
     * Params: $projet le projet associé
     *
     * Retourne: La nouvelle version
     *
     ************************************************/

    public function supprimerVersion(Version $version): void
    {
        $sj = $this->sj;
        $em = $this->em;

        // suppression des fichiers liés à la version
        $this->__effacerDonnees($version);

        // Suppression des dac associés
        foreach ($version->getDac() as $dac) {
            $em->remove($dac);
        }

        // Suppression des collaborateurs
        foreach ($version->getCollaborateurVersion() as $collaborateurVersion) {
            $em->remove($collaborateurVersion);
        }

        // Suppression des demandes de formation
        foreach ($version->getFormationVersion() as $formationVersion) {
            $em->remove($formationVersion);
        }

        // Suppression des expertises éventuelles
        $expertises = $version->getExpertise();
        foreach ($expertises as $expertise) {
            $em->remove($expertise);
        }

        // Suppression des rallonges
        $this->__effacerRallonges($version);

        // Ne devrait pas arriver !
        $projet = $version->getProjet();
        if (null === $projet) {
            $sj->warningMessage(__METHOD__.':'.__LINE__.' version '.$idVersion.' sans projet supprimée');
        } else {
            // $projet = $em->getRepository(Projet::class)->findOneBy(['idProjet' => $idProjet]);

            // On met le champ version derniere a NULL
            $projet->setVersionDerniere(null);
            $em->persist($projet);
            // $em->flush();
        }

        // On supprime la version
        // Du coup la versionDerniere est mise à jour par l'EventListener
        $em->remove($version);
        $em->flush();

        // Si pas d'autre version, on supprime le projet
        if (null !== $projet && null !== $projet->getVersion() && 0 === count($projet->getVersion())) {
            $this->__supprimerProjet($projet);
        }
    }

    /*************************************************************
     * Efface les données liées à une version de projet
     *
     *  - Les fichiers img_* et *.pdf du répertoire des figures
     *  - Le fichier de signatures s'il existe
     *********************************************************/
    private function __effacerDonnees(Version $version): void
    {
        $sv = $this->sv;

        // Les figures et les doc attachés
        $img_dir = $sv->imageDir($version);
        array_map('unlink', glob("$img_dir/img*"));
        array_map('unlink', glob("$img_dir/*.pdf"));

        // Les signatures
        $fiche = $sv->getSigne($version);
        if (null !== $fiche) {
            unlink($fiche);
        }
    }

    // effacer toutes les rallonges d'une version
    private function __effacerRallonges(Version $version)
    {
        $em = $this->em;

        // Effacer les rallonges
        foreach ($version->getRallonge() as $r) {
            // Effacer les Dars !
            foreach ($r->getDars() as $d) {
                $em->remove($d);
            }
            $em->remove($r);
        }
        $em->flush();
    }

    /**************
     * Calcule le prochain id de projet, à partir des projets existants
     *
     * Params: $anne   L'année considérée
     *         $type   Le type de projet
     *
     *
     * Return: Le nouvel id, ou null en cas d'erreur
     *
     ***************/
    private function nextProjetId($annee, $type): string
    {
        if (intval($annee) >= 2000) {
            $annee = $annee - 2000;
        }

        $prefix = $this->prj_prefix[$type];
        $numero = $this->em->getRepository(Projet::class)->getLastNumProjet($annee, $prefix);
        // $this->sj->debugMessage("$annee -> $type -> $numero");
        // $this->sj->debugMessage(print_r($this->prj_prefix,true));

        $id = $prefix.$annee.sprintf("%'.03d", $numero + 1);

        // $this->sj->debugMessage("$prefix $numero $id");
        return $id;
    }

    /***********
    * Renvoie le méta état du projet passé en paramètre, c'est-à-dire
    * un "état" qui n'est pas utilisé dans les workflows mais qui peut être
    * affiché et qui a du sens pour les utilisateurs
    ************************************************************/
    public function getMetaEtat(Projet $p): string
    {
        $etat_projet = $p->getEtatProjet();
        $type_projet = $p->gettypeProjet();

        // Projet terminé
        if (Etat::TERMINE === $etat_projet) {
            return 'TERMINE';
        }

        // $veract  = $this->versionActive($p);
        $verder = $p->derniereVersion();

        // Ne doit pas arriver: un projet a toujours une dernière version !
        // Peut-être la BD est-elle en rade donc on utilise le logger
        if (null === $verder) {
            $this->log->error(__METHOD__.':'.__LINE__.'Incohérence dans la BD: le projet '.
                                            $p->getIdProjet()." version active: $p n'a PAS de dernière version !");

            return 'INCONNU';
        }

        $etat_version = $verder->getEtatVersion();
        if (Etat::EDITION_DEMANDE === $etat_version) {
            return 'EDITION';
        } elseif (Etat::EDITION_EXPERTISE === $etat_version) {
            return 'EXPERTISE';
        } elseif (Etat::ACTIF === $etat_version) {
            return 'ACCEPTE';
        }

        // quelques jours avant la fin du projet: le projet est encore actif mais il
        // se grouiller de le renouveler si on veut continuer
        elseif (Etat::ACTIF_R === $etat_version) {
            return 'NONRENOUVELE';
        }

        // Si la dernière version est terminée et le projet renouvelable, il est en standby
        // ie on ne peut pas calculer mais on peut encore renouveler
        elseif (Etat::TERMINE === $etat_version) {
            return 'STANDBY';
        }

        return 'INCONNU';
    }

    /**
     * Liste tous les projets qui ont une version cette annee
     *       Utilise par ProjetController et AdminuxController, et aussi par StatistiquesController.
     *
     * Param : $annee
     *
     * Return: [ $projets, $total ] Un tableau de tableaux pour les projets, et les données consolidées
     *
     * NOTE - Si un projet a DEUX VERSIONS et change de responsable, donc de laboratoire, au cours de l'année,
     *        on affiche les données de la VERSION A (donc celles du début d'année)
     *        Cela peut conduire à une erreur à la marge dans les statistiques
     */

    /***********
     * Renvoie la liste des projets dynamiques qui ont une version en cours cette année
     * $annee      = Année - int, soit 0 (défaut) soit >2000 (ex. 2022)
     *
     * Return: un tableau de trois tableaux:
     *         - Le tableau des projets
     *         - Le tableau des données consolidées
     *         - Le tableau de la répartition entre les ressources
     *
     ********************/
    public function projetsDynParAnnee($annee = 0): array
    {
        $sroc = $this->sroc;
        $sdac = $this->sdac;
        $em = $this->em;

        // une version dont l'état se retrouve dans ce tableau ne sera pas comptée dans les données consolidées
        // (nombre de projets, heures demandées etc)
        $a_filtrer = [Etat::CREE_ATTENTE, Etat::EDITION_DEMANDE, Etat::ANNULE];

        // Données consolidées - Projets dynamiques
        $type = 'dyn';

        $total = [];
        $total[$type] = [];
        $total[$type]['prj'] = 0;  // Nombre de projets
        $total[$type]['demande'] = []; // Heures demandées par ressource
        $total[$type]['attribution'] = []; // Heures attribuées par ressource

        $noms = $sroc->getNoms();
        foreach ($noms as $nr) {
            $total[$type]['demande'][$nr] = 0;
            $total[$type]['attribution'][$nr] = 0;
        }

        $repartition[$type] = []; // Répartition des attributions entre les ressources
        for ($i = 0; $i < 2 ** count($noms); ++$i) {
            $repartition[$type][$i] = 0;
        }

        // Conso - PAS PRISE EN COMPTE POUR L'INSTANT !

        // Les versions qui ont été actives une partie de l'année
        // Elles sont triées selon la date de démarrage (les plus récentes en dernier)
        $versions = $this->getVersionsDynParAnnee($annee);

        // Il peut y avoir plusieurs versions par projet, on conserve les données des projets
        // $projets est un tableau associatif indexé par $p_id
        $projets = [];

        // Boucle sur les versions
        foreach ($versions as $v) {
            $p_id = $v->getProjet()->getIdProjet();

            // Projet déjà créé
            if (isset($projets[$p_id])) {
                $p = $projets[$p_id];
            } else {
                $p = [];
                ++$total[$type]['prj'];
                foreach ($noms as $nr) {
                    $p['demande'][$nr] = 0;
                    $p['attribution'][$nr] = 0;
                }
                $p['p'] = $v->getProjet();
                $p['v'] = $v;
                $p['metaetat'] = $this->getMetaEtat($p['p']);
            }

            $repkey = 0;
            $c = count($noms) - 1;
            foreach ($v->getDac() as $dac) {
                $nr = $sroc->getNomComplet($dac->getRessource());
                $p['demande'][$nr] = $sdac->getDemandeConsolidee($dac);
                $p['attribution'][$nr] = $sdac->getAttributionConsolidee($dac);
                $total[$type]['demande'][$nr] += $p['demande'][$nr];
                $total[$type]['attribution'][$nr] += $p['attribution'][$nr];
            }
            $projets[$p_id] = $p;
        }

        // Calcul de la répartition
        foreach ($projets as $p) {
            $c = count($noms) - 1;
            $repkey = 0;
            foreach ($noms as $nr) {
                if (0 !== $p['attribution'][$nr]) {
                    $repkey += 2 ** $c;
                }
                --$c;
            }
            ++$repartition[$type][$repkey];
        }

        return [$projets, $total, $repartition];
    }

    /***********
     * Renvoie la liste des rallonges de projets dynamiques associées à une version en cours cette année
     * $annee      = Année - int, soit 0 (défaut) soit >2000 (ex. 2022)
     *
     * Return: le tableau des rallonges
     *
     ********************/
    public function rallongesDynParAnnee($annee = 0): array
    {
        $sroc = $this->sroc;
        $em = $this->em;

        // Les versions qui ont été actives une partie de l'année
        // Elles sont triées selon la date de démarrage (les plus récentes en dernier)
        $versions = $this->getVersionsDynParAnnee($annee);

        // Pour chaque version, les rallonges associées à cette version, quelque soit son état
        $rallonges = [];
        foreach ($versions as $v) {
            $rallonges = array_merge($rallonges, iterator_to_array($v->getRallonge()));
        }

        return $rallonges;
    }

    /*********************************
     * Renvoie la liste des versions de projets dynamiques de l'année passée en paramètres
     * Si annee vaut zéro, récupère toutes les versions de projets dynamiques
     *
     ************************************************/

    private function getVersionsDynParAnnee(int $annee): array
    {
        $em = $this->em;
        $sv = $this->sv;

        $ttes_versions = $this->em->getRepository(Version::class)->findBy(['typeVersion' => Projet::PROJET_DYN]);

        if (0 === $annee) {
            return $ttes_versions;
        }

        // On ne garde que les versions qui ont été actives cette année
        $versions = [];
        foreach ($ttes_versions as $v) {
            if ($sv->isAnnee($v, $annee)) {
                $versions[] = $v;
            }
        }

        return $versions;
    }

    /*
     * Appelle projetsParAnnee et renvoie les tableaux suivants, indexés par le critère
     *
     *    - Nombre de projets
     *    - Heures demandées
     *    - Heures attribuées
     *    - Heures consommées
     *    - Liste des projets
     *
     * $annee   = Année
     * $sess_lbl= 'A', 'B', 'AB'
     * $critere = Un nom de getter de Version permettant de consolider partiellement les données
     *            Le getter renverra un acronyme (laboratoire, établissement etc)
     *            (ex = getAcroLaboratoire())
     *
     * Fonction utilisée pour les statistiques et pour le bilan annuel
     *
     * NOTE - Si $sess_lbl vaut A ou B on ne renvoie PAS les projets fil de l'eau
     *        Si $sess_lbl vaut AB on renvoie AUSSI les projets fil de l'eau
     *        On ne tient PAS compte des versions en état EDITION_DEMANDE
     *
     * TODO - Utilisé par les statistiques mais les statistiques sont à refaire
     *        La notion de session est supprimée ($sess_lbl) donc sans doute à réécrire
     *

     */
    public function projetsParCritere($annee, $sess_lbl, $critere): array
    {
        $sv = $this->sv;

        $projets = $this->projetsParAnnee($annee, false, false, $sess_lbl)[0];

        // On filtre complètement les projets qui ont déjà été partiellement filtrés dans projetsParAnnee
        $a_filtrer = [Etat::CREE_ATTENTE, Etat::EDITION_DEMANDE, Etat::ANNULE];

        // La liste des acronymes
        $acros = [];

        // Ces quatre tableaux sont indexés par l'acronyme ($acro)
        $num_projets = [];
        $num_projets_n = [];    // nouveaux projets
        $num_projets_r = [];    // renouvellements
        $liste_projets = [];
        $dem_heures = [];
        $attr_heures = [];
        $conso = [];
        $conso_gpu = [];

        // Remplissage des quatre tableaux précédents
        foreach ($projets as $p) {
            $v = (null === $p['vb']) ? $p['va'] : $p['vb'];

            // Filtrage !
            if (in_array($v->getEtatVersion(), $a_filtrer)) {
                continue;
            }
            if ('AB' !== $sess_lbl && 1 !== $v->getTypeVersion()) {
                continue;
            }

            $acro = $v->$critere();
            if ('' === $acro) {
                $acro = 'Autres';
            }

            if (!in_array($acro, $acros)) {
                $acros[] = $acro;
            }
            if (!array_key_exists($acro, $num_projets)) {
                $num_projets[$acro] = 0;
            }
            if (!array_key_exists($acro, $num_projets_n)) {
                $num_projets_n[$acro] = 0;
            }
            if (!array_key_exists($acro, $num_projets_r)) {
                $num_projets_r[$acro] = 0;
            }
            if (!array_key_exists($acro, $dem_heures)) {
                $dem_heures[$acro] = 0;
            }
            if (!array_key_exists($acro, $attr_heures)) {
                $attr_heures[$acro] = 0;
            }
            if (!array_key_exists($acro, $conso)) {
                $conso[$acro] = 0;
            }
            if (!array_key_exists($acro, $conso_gpu)) {
                $conso_gpu[$acro] = 0;
            }
            if (!array_key_exists($acro, $liste_projets)) {
                $liste_projets[$acro] = [];
            }

            ++$num_projets[$acro];
            if ($sv->isNouvelle($v)) {
                ++$num_projets_n[$acro];
            } else {
                ++$num_projets_r[$acro];
            }

            $liste_projets[$acro][] = $p['p']->getIdProjet();

            if (null !== $p['va']) {
                $dem_heures[$acro] += $p['va']->getDemHeuresTotal();
            }
            if (null !== $p['vb']) {
                $dem_heures[$acro] += $p['vb']->getDemHeuresTotal();
            }

            $attr_heures[$acro] += $p['attrib'];
            $conso[$acro] += $p['c'];
            $conso_gpu[$acro] += $p['g'];
        }
        asort($acros);

        return [$acros, $num_projets, $liste_projets, $dem_heures, $attr_heures, $conso, $num_projets_n, $num_projets_r, $conso_gpu];
    }

    /**
     * Filtre la version passee en paramètres, suivant qu'on a demandé des trucs sur les données ou pas
     *        Utilise par donneesParProjet
     *        Modifie le paramètre $p
     *        Renvoie true/false suivant qu'on veut garder la version ou pas.
     *
     * Param : $v La version
     *         $p [inout] Tableau représentant le projet
     *
     * Ajoute des champs à $p (voir le code), ainsi que deux flags:
     *         - 'stk' projet ayant demandé du stockage
     *         - 'ptg' projet ayant demandé du partage
     *
     * Return: true/false le 'ou' de ces deux flags
     */
    private function donneesParProjetFiltre($v, &$p): bool
    {
        $keep_it = false;
        $p = [];
        $p['p'] = $v->getProjet();
        $p['stk'] = false;
        $p['ptg'] = false;
        $p['sondVolDonnPerm'] = $v->getSondVolDonnPerm();
        $p['sondVolDonnPermTo'] = preg_replace('/^(\d+) .+/', '${1}', $p['sondVolDonnPerm']);
        $p['sondJustifDonnPerm'] = $v->getSondJustifDonnPerm();
        $p['dataMetaDataFormat'] = $v->getDataMetaDataFormat();
        $p['dataNombreDatasets'] = $v->getDataNombreDatasets();
        $p['dataTailleDatasets'] = $v->getDataTailleDatasets();
        if (null !== $p['sondVolDonnPerm']
            && '< 1To' !== $p['sondVolDonnPerm']
            && '1 To' !== $p['sondVolDonnPerm']
            && false === strpos($p['sondVolDonnPerm'], 'je ne sais')
        ) {
            $keep_it = $p['stk'] = true;
        }
        if (null !== $p['dataMetaDataFormat'] && false === strstr($p['dataMetaDataFormat'], 'intéressé')) {
            $keep_it = $p['ptg'] = true;
        }
        if (null !== $p['dataNombreDatasets'] && false === strstr($p['dataNombreDatasets'], 'intéressé')) {
            $keep_it = $p['ptg'] = true;
        }
        if (null !== $p['dataTailleDatasets'] && false === strstr($p['dataTailleDatasets'], 'intéressé')) {
            $keep_it = $p['ptg'] = true;
        }

        return $keep_it;
    }

    /*
     * Le user connecté a-t-il accès à $projet ?
     * Si OBS (donc ADMIN) ou PRESIDENT = La réponse est Oui
     * Si VALIDEUR et le projet est de type 4 (dynamique) = La réponse est OUI
     * Sinon c'est plus compliqué, on appelle userProjetACL...
     *
     * param:  $projet
     * return: true/false
     *
     *****/
    public function projetACL(Projet $projet): bool
    {
        if ($this->sac->isGranted('ROLE_OBS') || $this->sac->isGranted('ROLE_PRESIDENT')) {
            return true;
        } elseif (Projet::PROJET_DYN === $projet->getTypeProjet()) {
            return true;
        } else {
            return $this->userProjetACL($projet);
        }
    }

    /***
     *
     * Le user connecté a-t-il accès à au moins une version de $projet ?
     *
     *****/
    private function userProjetACL(Projet $projet): bool
    {
        $user = $this->token->getUser();
        foreach ($projet->getVersion() as $version) {
            if (true === $this->userVersionACL($version, $user)) {
                return true;
            }
        }

        return false;
    }

    // nous vérifions si un utilisateur a le droit d'accès à une version
    public static function userVersionACL(Version $version, Individu $user): bool
    {
        // nous vérifions si $user est un collaborateur de cette version
        if ($version->isCollaborateur($user)) {
            return true;
        }

        // nous vérifions si $user est un expert de cette version
        if ($version->isExpertDe($user)) {
            return true;
        }

        // nous vérifions si $user est un expert d'une rallonge
        foreach ($version->getRallonge() as $rallonge) {
            // $e = $rallonge->getExpert();
            // if ($e !== null && $user->isEqualTo($rallonge->getExpert())) return true;
            if ($rallonge->isExpertDe($user)) {
                return true;
            }
        }

        // nous vérifions si $user est un expert de la thématique
        if ($version->isExpertThematique($user)) {
            return true;
        }

        return false;
    }

    /************************************************
     * Renvoie le chemin vers le rapport d'activité s'il existe, null s'il n'y a pas de RA
     *
     * Si $annee==null, calcule l'année précédente l'année de la session
     * (OK pour sessions de type A !)
     *
     ***********************/
    public function getRapport(Projet $projet, $annee): ?string
    {
        $rapport_directory = $this->rapport_directory;
        $dir = $rapport_directory;
        if (null === $dir) {
            return null;
        }

        $file = $dir.'/'.$annee.'/'.$annee.$projet->getIdProjet().'.pdf';
        if (file_exists($file) && !is_dir($file)) {
            return $file;
        } else {
            return null;
        }
    }

    /*************************************************
     * Teste pour savoir si un projet donné a un rapport d'activité
     * On regarde dans la base de données ET dans les fichiers (!)
     *
     * Return: true/false
     *
     ********************************/
    public function hasRapport(Projet $projet, $annee): bool
    {
        $rapportActivite = $this->em->getRepository(RapportActivite::class)->findOneBy(
            [
                                'projet' => $projet,
                                'annee' => $annee,
                                ]
        );

        if (null === $rapportActivite) {
            return false;
        }
        if (null === $this->getRapport($projet, $annee)) {
            return false;
        } else {
            return true;
        }
    }

    /**************************
     * Renvoie la taille du rapport d'activité en Ko
     * On lit la taille dans la base de données
     *
     *************************************/
    public function getSizeRapport(Projet $projet, $annee): int
    {
        $rapportActivite = $this->em->getRepository(RapportActivite::class)->findOneBy(
            [
                                'projet' => $projet,
                                'annee' => $annee,
                                ]
        );

        if (null !== $rapportActivite) {
            return intdiv($rapportActivite->getTaille(), 1024);
        } else {
            return 0;
        }
    }

    /*
     * Renvoie un tableau contenant la ou les versions de l'année passée en paramètres
     */
    public function getVersionsAnnee(Projet $projet, $annee): array
    {
        $subAnnee = substr(strval($annee), -2);
        $repository = $this->em->getRepository(Version::class);
        $versionA = $this->em->getRepository(Version::class)->findOneBy(['idVersion' => $subAnnee.'A'.$projet->getIdProjet(), 'projet' => $projet]);
        $versionB = $this->em->getRepository(Version::class)->findOneBy(['idVersion' => $subAnnee.'B'.$projet->getIdProjet(), 'projet' => $projet]);

        $versions = [];
        if (null !== $versionA) {
            $versions['A'] = $versionA;
        }
        if (null !== $versionB) {
            $versions['B'] = $versionB;
        }

        return $versions;
    }

    /*
     * Effacer les utilisateurs qui n'ont pas de structures de données associées:
     *         - Pas collaborateurs
     *         - Pas d'expertises
     *         - Pas de privilèges
     *         - Pas de users
     *
     * Efface les Users correspondants à ces individus !
     * Renvoie un tableau contenant les clones des individus effacés
     * TODO - Un peu zarbi tout de même
     */

    public function effacer_utilisateurs($individus = null): array
    {
        $individus_effaces = [];
        $em = $this->em;
        $repo_ind = $em->getRepository(Individu::class);
        $repo_cv = $em->getRepository(CollaborateurVersion::class);
        $repo_exp = $em->getRepository(Expertise::class);

        $individus = $repo_ind->findAll();
        foreach ($individus as $individu) {
            if ($individu->getAdmin()) {
                continue;
            }
            if ($individu->getObs()) {
                continue;
            }
            if ($individu->getExpert()) {
                continue;
            }

            if (!(null === $repo_cv->findOneBy(['collaborateur' => $individu]))) {
                continue;
            }
            if (!(null === $repo_exp->findOneBy(['expert' => $individu]))) {
                continue;
            }

            $individus_effaces[] = clone $individu;
            foreach ($em->getRepository(Sso::class)->findBy(['individu' => $individu]) as $sso) {
                $em->remove($sso);
            }

            foreach ($individu->getUser() as $u) {
                $em->remove($u);
            }

            foreach ($individu->getClessh() as $k) {
                $em->remove($k);
            }

            // TODO - Supprimer les invitations... ne devraient pas exister mais il fuadrait le vérifier !

            $this->sj->infoMessage("L'individu ".$individu.' a été effacé ');
            $em->remove($individu);
        }

        $em->flush();

        return $individus_effaces;
    }

    /*
     * Calcul de la dernière version d'un projet - Utilisé par \App\EventListener\ProjetDerniereVersion
     * TODO : Eclaircir le mismatch nbversion string / int
     */
    public function calculVersionDerniere(Projet $projet): ?Version
    {
        $sj = $this->sj;
        if (null === $projet->getVersion()) {
            $sj->throwException(__METHOD__.':'.__LINE__." Projet $projet = PAS DE VERSION");
        }

        $iterator = $projet->getVersion()->getIterator();

        $iterator->uasort(function ($a, $b) {
            $sj = $this->sj;

            $nba = $a->getNbVersion();
            if (null === $a->getNbVersion()) {
                $nba = 0;
            }

            $nbb = $b->getNbVersion();
            if (null === $b->getNbVersion()) {
                $nbb = 0;
            }

            return $nba > $nbb;
        });

        $sortedVersions = iterator_to_array($iterator);
        $result = end($sortedVersions);
        if (false === $result) {
            return null;
        }

        // On met à jour projet si nécessaire
        // dd($projet->getVersionDerniere(),$result);
        if ($projet->getVersionDerniere() !== $result) {
            $projet->setVersionDerniere($result);
            $em = $this->em;
            $em->persist($projet);
            $em->flush($projet);
        }

        return $result;
    }

    /**
     * calculVersionActive.
     */
    public function calculVersionActive(Projet $projet): ?Version
    {
        $em = $this->em;
        $versionActive = $projet->getVersionActive();

        // Si le projet est terminé = renvoyer null
        if (Etat::TERMINE === $projet->getEtatProjet()) {
            if (null !== $versionActive) {
                $projet->setVersionActive(null);
                $em->persist($projet);
                // $em->flush();
            }

            return null;
        }

        // Vérifie que la version active est vraiment active
        if (null !== $versionActive
          && (Etat::ACTIF === $versionActive->getEtatVersion() || Etat::ACTIF_R === $versionActive->getEtatVersion() || Etat::NOUVELLE_VERSION_DEMANDEE === $versionActive->getEtatVersion())
        ) {
            return $versionActive;
        }

        // Sinon on la recherche, on la garde, puis on la renvoie
        $result = null;
        foreach (array_reverse($projet->getVersion()->toArray()) as $version) {
            if (Etat::ACTIF === $version->getEtatVersion()
                || Etat::NOUVELLE_VERSION_DEMANDEE === $version->getEtatVersion()
                || Etat::ACTIF_R === $version->getEtatVersion()
                || Etat::EN_ATTENTE === $version->getEtatVersion()
                || Etat::ACTIF_TEST === $version->getEtatVersion()) {
                $result = $version;
                break;
            }
        }

        // update BD
        if ($versionActive !== $result) { // seulement s'il y a un changement
            $projet->setVersionActive($result);
            $em->persist($projet);
            // $em->flush();
        }

        return $result;
    }

    public function versionActive(Projet $projet): ?Version
    {
        return $this->calculVersionActive($projet);
    }

    /**
     * getVersionsNonTerminees.
     *
     * renvoie les versions non terminées d'un projet
     *
     *************************************************/
    public function getVersionsNonTerminees(Projet $p): array
    {
        $versions = $p->getVersion();
        $vnt = [];
        foreach ($versions as $v) {
            if (Etat::ANNULE !== $v->getEtatVersion() && Etat::TERMINE !== $v->getEtatVersion()) {
                $vnt[] = $v;
            }
        }

        return $vnt;
    }

    /***********************************************
     * Renvoie true s'il y a des choses non acquittées sur ce projet,
     * c-à-d si au moins une des ressources n'est pas acquittée
     * Renvoie false si tout est acquitté
     *******************************************************/
    public function getTodofConsolide(Projet $projet): bool
    {
        $sdac = $this->sdac;

        $version = $projet->getVersionActive();
        if (null === $version) {
            return false;
        }

        foreach ($version->getDac() as $dac) {
            if ($sdac->getTodofConsolide($dac)) {
                return true;
            }
        }

        return false;
    }
}
