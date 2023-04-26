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

use App\Utils\Functions;
use App\GramcServices\Etat;

use App\Entity\Projet;
use App\Entity\Version;
use App\Entity\Rallonge;
use App\Entity\Individu;
use App\Entity\CollaborateurVersion;
use App\Entity\Laboratoire;
use App\Entity\User;
use App\Entity\Serveur;
use App\Entity\Ressource;
use App\Entity\Compta;
use App\Entity\Clessh;
use App\Entity\Param;

use App\GramcServices\ServiceNotifications;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceProjets;
use App\GramcServices\ServiceSessions;
use App\GramcServices\ServiceRessources;
use App\GramcServices\ServiceDacs;
use App\GramcServices\GramcDate;
use App\GramcServices\ServiceVersions;
use App\GramcServices\ServiceUsers;
use App\GramcServices\Cron\Cron;

use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Doctrine\ORM\EntityManagerInterface;

/**
 * AdminUx controller: Commandes curl envoyées par l'administrateur unix
 *
 * @Route("/adminux")
 */
class AdminuxController extends AbstractController
{
    public function __construct(
        private ServiceNotifications $sn,
        private ServiceJournal $sj,
        private ServiceProjets $sp,
        private ServiceSessions $ss,
        private GramcDate $grdt,
        private ServiceVersions $sv,
        private ServiceUsers $su,
        private ServiceRessources $sroc,
        private ServiceDacs $sdac,
        private Cron $cr,
        private TokenStorageInterface $tok,
        private EntityManagerInterface $em
    ) {}

    /**
     * Met à jour la consommation pour un projet donné
     *
     * @Route("/projet/setconso", name="set_conso", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     */

    // exemple: curl --netrc -X POST -d '{ "projet": "M12345", "ressource": "TURPAN", "conso": "10345" }'https://.../adminux/projet/setconso
    public function setconsoAction(Request $request): Response
    {
        $em = $this->em;
        $sj = $this->sj;
        $sroc = $this->sroc;
        $su = $this->su;

        $content  = json_decode($request->getContent(), true);
        if ($content === null)
        {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de données");
            return new Response(json_encode(['KO' => 'Pas de données']));
        }
        if (empty($content['projet']))
        {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de projet");
            return new Response(json_encode(['KO' => 'Pas de projet']));
        }
        else
        {
            $idProjet = $content['projet'];
        }
        if (empty($content['ressource']))
        {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de ressource");
            return new Response(json_encode(['KO' => 'Pas de ressource']));
        }
        else
        {
            $nomRessource = $content['ressource'];
        }
        if (! isset($content['conso'])) {
            $sj->errorMessage("AdminUxController::setconsoAction - Pas de conso");
            return new Response(json_encode(['KO' => 'Pas de conso']));
        } else {
            $conso = $content['conso'];
        }

        $error = [];

        // Pas de requêtes actuellement pour trouver une ressource avec son nom complet
        // Donc on balaie toutes les ressources... qui ne devraient pas être super-nombreuses non plus
        $ressources = $sroc->getRessources();
        $ressource = null;
        foreach ($ressources as $r)
        {
            if ($sroc->getnomComplet($r) === $nomRessource)
            {
                $ressource = $r;
                break;
            }
        }

        if ($ressource === null)
        {
            $error[] = 'No ressource ' . $nomRessource;
        }

        if ($ressource !== null)
        {
            $serveur = $ressource -> getServeur();
        }
        else
        {
            $serveur = null;
            $error[] = 'No serveur ';
        }
        
        // On vérifie que le user connecté est bien autorisé à agir sur le serveur de cette ressource
        if ($serveur != null && ! $this->checkUser($serveur))
        {
           $error[] = 'ACCES INTERDIT A ' . $ressource; 
        }

        // On vérifie que le projet existe 
        $projet = $em->getRepository(Projet::class)->find($idProjet);
        if ($projet === null) {
            $error[] = 'No Projet ' . $idProjet;
        }
        else
        {
            $version = $projet->getVersionActive();
            if ($version === null)
            {
                $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de version active pour $projet");
                return new Response(json_encode(['KO' => 'Pas de version active']));
            }
        }

        // On vérifie que la conso est >= 0
        if ($conso < 0)
        {
            $error[] = "conso doit être un entier positif ou nul, pas $conso";
        }
        
        if ($error != []) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - " . print_r($error, true));
            return new Response(json_encode(['KO' => $error ]));
        }

        // C'est OK - On positionne la conso
        $dacs = $version->getdac();
        foreach ($dacs as $d)
        {
            if ($d->getRessource() === $ressource)
            {
                $d->setConsommation(intval($conso));
                $em->flush();
            }
        }

        $sj -> infoMessage(__METHOD__ . "conso ajustée pour $projet");
        return new Response(json_encode('OK'));
    }   

    ///////////////////////////////////////////////////////////////////////////////

    /**
     * set loginname
     *
     * @Route("/utilisateurs/setloginname", name="set_loginname", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * Positionne le loginname du user demandé dans la version active ou EN_ATTENTE du projet demandé
     *
     */

    // exemple: curl --netrc -X POST -d '{ "loginname": "toto@TURPAN", "idIndividu": "6543", "projet": "P1234" }'https://.../adminux/utilisateurs/setloginname
    public function setloginnameAction(Request $request): Response
    {
        $em = $this->em;
        $sj = $this->sj;
        $su = $this->su;

        $content  = json_decode($request->getContent(), true);
        if ($content === null) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de données");
            return new Response(json_encode(['KO' => 'Pas de données']));
        }
        if (empty($content['loginname'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de nom de login");
            return new Response(json_encode(['KO' => 'Pas de nom de login']));
        } else {
            $loginname = $content['loginname'];
        }
        if (empty($content['projet'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de projet");
            return new Response(json_encode(['KO' => 'Pas de projet']));
        } else {
            $idProjet = $content['projet'];
        }
        if (empty($content['idIndividu'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de idIndividu");
            return new Response(json_encode(['KO' => 'Pas de idIndividu']));
        } else {
            $idIndividu = $content['idIndividu'];
        }

        $error = [];
        $projet      = $em->getRepository(Projet::class)->find($idProjet);
        if ($projet === null) {
            $error[]    =   'No Projet ' . $idProjet;
        }

        $individu = $em->getRepository(Individu::class)->find($idIndividu);
        if ($individu === null) {
            $error[]    =   'No idIndividu ' . $idIndividu;
        }

        $loginname_p = $su -> parseLoginname($loginname);
        $serveur = $em->getRepository(Serveur::class)->findOneBy( ["nom" => $loginname_p['serveur']]);
        if ($serveur === null)
        {
           $error[] = 'No serveur ' . $loginname_p['serveur'];
        }

        // On vérifie que le user connecté est bien autorisé à agir sur ce serveur
        if ($serveur != null && ! $this->checkUser($serveur))
        {
           $error[] = 'ACCES INTERDIT A ' . $loginname_p['serveur']; 
        }

        if ($error != []) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - " . print_r($error, true));
            return new Response(json_encode(['KO' => $error ]));
        }

        $u = $su->getUser($individu, $projet, $serveur);
        if ( $u->getLogin() === false)
        {
            $msg = "L'ouverture de compte n'a pas été demandée pour ce collaborateur";
            $sj->warningMessage(__METHOD__ . ':' . __FILE__ . " - $msg");
            return new Response(json_encode(['KO' => $msg]));
        }
        if ( $u->getLoginname() != 'nologin' && $u->getLoginname() != null)
        {
            $msg = "Commencez par appeler clearloginname";
            $sj->warningMessage(__METHOD__ . ':' . __FILE__ . " - $msg ");
            return new Response(json_encode(['KO' => $msg]));
        }

        // Maintenant on peut positionner le nom de login
        $u->setLoginname($loginname_p['loginname']);
        $em->persist($u);
        try
        {
            $em->flush();
        }
        catch (\Exception $e)
        {
            $msg = "Exception $e";
            // ne marche pas car on est dans un traitement d'exception de $em
            //$sj -> warningMessage("__METHOD__ . ':' . __FILE__ .  $e");
            return new Response(json_encode(['KO - Erreur de base de données (nom de login dupliqué ?)']));
        }

        $sj -> infoMessage(__METHOD__ . "user $u modifié");
        return new Response(json_encode('OK'));
    }

    /**
      * set password
      *
      * @Route("/utilisateurs/setpassword", name="set_password", methods={"POST"})
      * @Security("is_granted('ROLE_ADMIN')")

      * Positionne le mot de passe du user demandé, à condition que ce user existe dans la table user
      */

    // curl --netrc -H "Content-Type: application/json" -X POST -d '{ "loginname": "bob@serveur", "password": "azerty", "cpassword": "qwerty" }' https://.../adminux/utilisateurs/setpassword

    public function setpasswordAction(Request $request): Response
    {
        $em = $this->em;
        $sj = $this->sj;

        $content  = json_decode($request->getContent(), true);
        if ($content === null) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de données");
            return new Response(json_encode(['KO' => 'Pas de données']));
        }
        if (empty($content['loginname'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de nom de login");
            return new Response(json_encode(['KO' => 'Pas de nom de login']));
        } else {
            $loginname = $content['loginname'];
        }

        if (empty($content['password'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de mot de passe");
            return new Response(json_encode(['KO' => 'Pas de mot de passe']));
        } else {
            $password = $content['password'];
        }

        if (empty($content['cpassword'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de version cryptée du mot de passe");
            return new Response(json_encode(['KO' => 'Pas de version cryptée du mot de passe']));
        } else {
            $cpassword = $content['cpassword'];
        }

        // Calcul de la date d'expiration
        $pwd_duree = $this->getParameter('pwd_duree');  // Le nombre de jours avant expiration du mot de passe
        $grdt = $this->grdt;
        $passexpir = $grdt->getNew()->add(new \DateInterval($pwd_duree));

        // Vérifie qu'on a le droit d'intervenir sur ce serveur
        if ( !$this->checkUser($loginname))
        {
            $msg = 'ACCES INTERDIT A CE SERVEUR'; 
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - " . $msg);
            return new Response(json_encode(['KO' => $msg ])); 
        }
        
        // Vérifie que ce loginname est connu
        try
        {
            $cv = $em->getRepository(User::class)->existsLoginname($loginname);
        }
        catch ( \Exception $e)
        {
            
            $msg = "'$loginname' n'est pas de la forme alice@serveur";
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - " . $msg);
            return new Response(json_encode(['KO' => $msg ]));
        }
        if ($cv==false) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - No user '$loginname' found in any projet");
            return new Response(json_encode(['KO' => "No user '$loginname' found in any projet" ]));
        }

        # Modifier le mot de passe
        else {
            $user = $em->getRepository(User::class)->findOneBy(['loginname' => $loginname]);
            if ($user==null) {
                $user = new User();
                $user->setLoginname($loginname);
                $user->setExpire(false);
            }

            // Le mot de passe est tronqué à 50 caractères, puis crypté
            $password = substr($password, 0, 50);
            $password = Functions::simpleEncrypt($password);
            $user->setPassword($password);
            $user->setPassexpir($passexpir);
            $user->setCpassword($cpassword);

            // On n'utilise pas Functions::sauvegarder parce que problèmes de message d'erreur
            // TODO - A creuser
            $em->persist($user);
            $em->flush($user);
            //Functions::sauvegarder( null, $em, $lg );

            $sj -> infoMessage(__METHOD__ . "Mot de passe de $loginname modifié");
            return new Response(json_encode(['OK' => '']));
        }
    }

    /**
      * clear password
      *
      * Efface le mot de passe temporaire pour le user passé en paramètres
      *
      * @Route("/users/clearpassword", name="clear_password", methods={"POST"})
      * @Route("/utilisateurs/clearpassword", name="clear_password", methods={"POST"})
      * @Security("is_granted('ROLE_ADMIN')")
      *
      * Efface le mot de passe du user demandé
      */

    // curl --netrc -H "Content-Type: application/json" -X POST -d '{ "loginname": "toto" }' https://.../adminux/users/clearpassword

    public function clearpasswordAction(Request $request): Response
    {
        $em = $this->em;
        $sj = $this->sj;

        $content  = json_decode($request->getContent(), true);
        if ($content === null) {
            $sj->errorMessage("__METHOD__ . ':' . __FILE__ .  - Pas de données");
            return new Response(json_encode(['KO' => 'Pas de donnees']));
        }
        if (empty($content['loginname'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de nom de login");
            return new Response(json_encode(['KO' => 'Pas de nom de login']));
        } else
        {
            $loginname = $content['loginname'];
        }

        // Vérifie qu'on a le droit d'intervenir sur ce serveur
        if ( !$this->checkUser($loginname))
        {
            $msg = 'ACCES INTERDIT A CE SERVEUR'; 

            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - " . $msg);
            return new Response(json_encode(['KO' => $msg ])); 
        }
        
        # Vérifie que ce loginname est connu
        try
        {
            $cv = $em->getRepository(User::class)->existsLoginname($loginname);
        }
        catch ( \Exception $e)
        {
            $msg = "'$loginname' n'est pas de la forme alice@serveur";
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - " . $msg);
            return new Response(json_encode(['KO' => $msg ]));
        }
        if ($cv==false) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - No user '$loginname' found in any projet");
            return new Response(json_encode(['KO' => "No user '$loginname' found in any projet" ]));
        }

        # effacer l'enregistrement
        else {
            $user = $em->getRepository(User::class)->findOneBy(['loginname' => $loginname]);
            if ($user==null) {
                $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - No password stored for '$loginname");
                return new Response(json_encode(['KO' => "No password stored for '$loginname'" ]));
            }

            $em->remove($user);
            $em->flush();
        }

        $sj -> infoMessage(__METHOD__ . "Mot de passe de $loginname effacé");
        return new Response(json_encode(['OK' => '']));
    }

    /**
      * clear loginname
      *
      * Efface le login name (en cas de fermeture d'un compte) pour le user passé en paramètres
      * Efface aussi le mot de passe s'il y en a un
      *
      * @Route("/users/clearloginname", name="clear_loginname", methods={"POST"})
      * @Route("/utilisateurs/clearloginname", name="clear_loginname", methods={"POST"})
      * @Security("is_granted('ROLE_ADMIN')")
      *
      * Efface le loginname s'il existe, ne fait rien sinon
      */

    // curl --netrc -H "Content-Type: application/json" -X POST -d '{ "loginname": "toto@SERVEUR", "projet":"P1234" }' https://.../adminux/utilisateurs/clearloginname

    public function clearloginnameAction(Request $request): Response
    {
        $em = $this->em;
        $sj = $this->sj;
        $token = $this->tok->getToken();
        
        $content  = json_decode($request->getContent(), true);
        if ($content === null) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de données");
            return new Response(json_encode(['KO' => 'Pas de donnees']));
        }
        if (empty($content['loginname'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de nom de login");
            return new Response(json_encode(['KO' => 'Pas de nom de login']));
        } else {
            $loginname = $content['loginname'];
        }
        if (empty($content['projet'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de projet");
            return new Response(json_encode(['KO' => 'Pas de projet']));
        } else {
            $idProjet = $content['projet'];
        }

        // Vérifie qu'on a le droit d'intervenir sur ce serveur
        if ( !$this->checkUser($loginname))
        {
            $msg = 'ACCES INTERDIT A CE SERVEUR'; 
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - " . $msg);
            return new Response(json_encode(['KO' => $msg ])); 
        }

        // Vérifie que ce loginname est connu
        $user = $em->getRepository(User::class)->findOneByLoginname($loginname);
        
        //return new Response(json_encode($cvs));
        if (!$user) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - No user '$loginname' found in any active version");
            return new Response(json_encode(['KO' => "No user '$loginname' found in any active version" ]));
        }
        else
        {
            $user->setLoginname(null);
            $em->persist($user);
            $em->flush();
        }

        $sj -> infoMessage(__METHOD__ . "Compte $loginname supprimé");
        return new Response(json_encode(['OK' => '']));
    }

    private function __getVersionInfo($v, bool $long): array
    {
        $sp = $this->sp;
        $sroc = $this->sroc;
        $sdac = $this->sdac;
        $em = $this->em;

        $r = [];
        $r['idProjet']        = $v->getProjet()->getIdProjet();
        $r['idVersion']       = $v->getIdVersion();
        $r['etatVersion']     = $v->getEtatVersion();
        $r['etatProjet']      = $v->getProjet()->getEtatProjet();

        if ($v->getStartDate() === null)
        {
            $r['startDate'] = null;
        }
        else
        {
            $r['startDate'] = $v->getStartDate()->format('Y-m-d');
        }
        
        if ($v->getEndDate() === null)
        {
            $r['endDate'] = null;
        }
        else
        {
            $r['endDate'] = $v->getEndDate()->format('Y-m-d');
        }
        
        if ($v->getLimitDate() === null)
        {
            $r['limitDate'] = null;
        }
        else
        {
            $r['limitDate'] = $v->getLimitDate()->format('Y-m-d');
        }
        
        $resp = $v->getResponsable();
        $r['mail']            = $resp === null ? null : $resp->getMail();
        if ($long)
        {
            $r['titre']      = $v->getPrjTitre();
            $r['expose']     = $v->getPrjExpose();
            $r['labo']       = $v->getPrjLLabo();
            $r['idLabo']     = $resp->getLabo()->getId();
            if ($v->getPrjThematique() != null)
            {
                $r['thematique'] = $v->getPrjThematique()->getLibelleThematique();
                $r['idthematique'] = $v->getPrjThematique()->getIdThematique();
            }
            else
            {
                $r['thematique'] = '';
                $r['idthematique'] = 0;
            }
        }

        $ressources = [];
        foreach ($v->getDac() as $dac)
        {
            $d = [];
            $d['attribution'] = $sdac->getAttributionConsolidee($dac);
            $d['demande'] = $sdac->getDemandeConsolidee($dac);
            $d['consommation'] = $dac->getConsommation();
            $ressources[$sroc->getNomComplet($dac->getRessource())] = $d;
        }
        $r['ressources'] = $ressources;

        return $r;
    }

    /**
     * get projets non terminés
     *
     * @Route("/projets/get", name="get_projets", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Exemples de données POST (fmt json):
     *             ''
     *             ou
     *             '{ "projet" : null     }' -> Tous les projets non terminés
     *
     *             '{ "projet" : "P01234" }' -> Le projet P01234
     *             ou
     *             '{ "projet" : "P01234", "long": "true" }'
     *
     * Pour le paramètre "long" voir la doc de versionGet
     *
     * Renvoie les informations utiles sur les projets non terminés, à savoir:
     *     - typeProjet
     *     - etatProjet
     *     - metaEtat
     *     - nepasterminer (True/False)
     *     - versionActive   -> On renvoie les mêmes données que getVersion
     *     - versionDerniere -> On renvoie les mêmes données que getVersion
     *
     * Données renvoyées pour versionActive et versionDerniere:
     *          idProjet    P01234
     *          idVersion   20AP01234
     *          mail        mail du responsable de la version
     *          attrHeures  Heures cpu attribuées
     *          quota       Quota sur la machine
     *          gpfs        sondVolDonnPerm stockage permanent demandé (pas d'attribution pour le stockage)
     *
     */
    // curl --netrc -H "Content-Type: application/json" -X POST -d '{ "projet": "P1234" }' https://.../adminux/projets/get

    public function projetsGetAction(Request $request): Response
    {
        $em = $this->em;
        $sp = $this->sp;
        $sj = $this->sj;
        $grdt = $this->grdt;
        $rep= $em->getRepository(Projet::class);

        $content  = json_decode($request->getContent(), true);
        //print_r($content);
        if ($content === null) {
            $id_projet = null;
            $long = false;

        } else {
            $id_projet  = (isset($content['projet'])) ? $content['projet'] : null;
            $long = (isset($content['long']))? $content['long']: false;
        }

        $p_tmp = [];
        $projets = [];
        if ($id_projet === null) {
            $projets = $rep->findNonTermines();
        } else {
            $p = $rep->findOneBy(["idProjet" => $id_projet]);
            if ($p != null) {
                $projets[] = $p;
            }
        }

        foreach ($projets as $p) {
            $data = [];
            $data['idProjet']   = $p->getIdProjet();
            $data['etatProjet'] = $p->getEtat();
            $data['metaEtat']   = $sp->getMetaEtat($p);
            $data['typeProjet'] = $p->getTypeProjet();
            //$data['consoTurpan'] = $sp->getConsoRessource($p,'gpu@TURPAN',$grdt);
            //$data['consoBoreale'] = $sp->getConsoRessource($p,'cpu@BOREALE',$grdt);
            $va = ($p->getVersionActive()!=null) ? $p->getVersionActive() : null;
            $vb = ($p->getVersionDerniere()!=null) ? $p->getVersionDerniere() : null;
            $v_data = [];
            foreach (["active"=>$va,"derniere"=>$vb] as $k=>$v) {
                if ($v != null) {
                    $v_data[$k] = $this->__getVersionInfo($v,$long);
                }
            }
            $data['versions'] = $v_data;
            $p_tmp[] = $data;
        }

        $sj -> infoMessage(__METHOD__ . " OK");
        return new Response(json_encode($p_tmp));
    }

    /**
     * get versions non terminées
     *
     * @Route("/version/get", name="get_version", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * Exemples de données POST (fmt json):
     *             ''
     *             ou
     *             '{ "projet" : null,     "session" : null }' -> Toutes les VERSIONS ACTIVES
     *
     *             '{ "projet" : "P01234" }'
     *             ou
     *             '{ "projet" : "P01234", "session" : null }' -> LA VERSION ACTIVE du projet P01234
     *
     *             '{ "session" : "20A"}
     *             ou
     *             '{ "projet" : null,     "session" : "20A"}' -> Toutes les versions de la session 20A
     *
     *             '{ "projet" : "P01234", "session" : "20A"}' -> La version 20AP01234
     *             ou
     *             '{ "version" : "01M22022" }' -> La version 01M22022
     * 
     * Version "longue" - Le paramètre "long" provoque l'envoi de données supplémentaires concernant la ou les versions:
     * -----------------------------------------------------------------------------------------------------------------
     * 
     *             '{ "projet" : "P01234", "session" : null, "long: true" }' -> LA VERSION ACTIVE du projet P01234
     * 
     * Donc on renvoie une ou plusieurs versions appartenant à différentes sessions, mais une ou zéro versions par projet
     * Les versions renvoyées peuvent être en état: ACTIF, EN_ATTENTE, NOUVELLE_VERSION_DEMANDEE si "session" vaut null
     * Les versions renvoyées peuvent être dans n'importe quel état (sauf ANNULE) si "session" est spécifiée
     *
     * Données renvoyées (fmt json):
     *                 idProjet    P01234
     *                 idSession   20A
     *                 idVersion   20AP01234
     *                 mail        mail du responsable de la version
     *                 attrHeures  Heures cpu attribuées (NON UTLISE)
     *                 attrHeuresUft Heures cpu attribuées à Uft
     *                 attrHeuresCriann Heures cpu attribuées à Criann
     *                 quota       Quota sur la machine
     *                 gpfs        sondVolDonnPerm stockage permanent demandé (pas d'attribution pour le stockage)
     *
     * Si "long" est spécifié on renvoie aussi:
     *                 titre       prjTitre
     *                 resume      prjResume
     *                 labo        prjLLabo
     *                 metadonnees dataMetaDataFormat
     *
     * curl --netrc -H "Content-Type: application/json" -X POST  -d '{ "projet" : "P1234" }' https://.../adminux/version/get
     *
     */
     public function versionGetAction(Request $request): Response
     {
        $em = $this->em;
        $sp = $this->sp;
        $sj = $this->sj;
        
        $versions = [];

        $content  = json_decode($request->getContent(),true);
        if ($content === null)
        {
            $id_projet = null;
            $id_version = null;
            $long = false;
        }
        else
        {
            $id_projet  = (isset($content['projet'])) ? $content['projet'] : null;
            $id_version = (isset($content['version']))? $content['version']: null;
            $long = (isset($content['long']))? $content['long']: false;
        }
        
        $v_tmp = [];

        // Une version particulière
        if ( $id_version != null)
        {
            $version = $em->getRepository(Version::class)->find($id_version);
            $v_tmp[] = $version;
        }
        
        // Tous les projets actifs
        elseif ($id_projet === null && $id_session === null)
        {
            $v_tmp = $em->getRepository(Version::class)->findAll();
        }

        // La version active d'un projet donné
        else
        {
            $projet = $em->getRepository(Projet::class)->find($id_projet);
            if ($projet != null) $v_tmp[]= $projet->getVersionActive();
        }

        // On ne garde que les versions actives... ou presque actives
        $etats = [Etat::ACTIF, Etat::EN_ATTENTE, Etat::NOUVELLE_VERSION_DEMANDEE];
        foreach ($v_tmp as $v)
        {
            if ($v === null) continue;
            if (in_array($v->getEtatVersion(),$etats,true))
            {
                $versions[] = $v;
            }
        }

        $retour = [];

        foreach ($versions as $v)
        {
            if ($v==null) continue;

            $r = $this->__getVersionInfo($v,$long);
            $r['idProjet']        = $v->getProjet()->getIdProjet();
            $r['idVersion']       = $v->getIdVersion();
            $r['etatVersion']     = $v->getEtatVersion();
            $r['etatProjet']      = $v->getProjet()->getEtatProjet();
            $r['mail']            = $v->getResponsable()->getMail();
            
            $retour[] = $r;
        };

        // print_r est plus lisible pour le déboguage
        // return new Response(print_r($retour,true));
        $sj -> infoMessage(__METHOD__ . " OK");
        return new Response(json_encode($retour));

     }

    /**
     * get utilisateurs
     *
     * @Route("/utilisateurs/get", name="get_utilisateurs", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * Exemples de données POST (fmt json):
     *             ''
     *             ou
     *             '{ "projet" : null,     "mail" : null }' -> Tous les collaborateurs avec login
     *
     *             '{ "projet" : "P01234" }'
     *             ou
     *             '{ "projet" : "P01234", "mail" : null }' -> Tous les collaborateurs avec login du projet P01234 (version ACTIVE)
     *
     *             '{ "mail" : "toto@exemple.fr"}'
     *             ou
     *             '{ "projet" : null,     "mail" : "toto@exemple.fr"}' -> Tous les projets dans lesquels ce collaborateur a un login (version ACTIVE de chaque projet)
     *
     *             '{ "projet" : "P01234", "mail" : "toto@exemple.fr" }' -> rien ou toto si toto avait un login sur ce projet
     *
     * On ne considère QUE les version actives et dernières de chaque projet non terminé
     *
     * On renvoie pour chaque version considérée, la liste des collaborateurs
     * tels que loginname != null (login créé, peut-être à supprimer si login==false),
     * OU loginname=null mais login==true ou clogin==true (login à créer)
     *
     * Données renvoyées (fmt json):
     *
     *             "toto@exemple.fr" : {
     *                  "idIndividu": 75,
     *                  "nom" : "Toto",
     *                  "prenom" : "Ernest",
     *                  "projets" : {
     *                  "P01234" : "toto",
     *                  "P56789" : "etoto"
     *                  }
     *              },
     *             "titi@exemple.fr": ...
     *
     *
     */

    // curl --netrc -H "Content-Type: application/json" -X POST  -d '{ "projet" : "P1234", "mail" : null }' https://.../adminux/utilisateurs/get
    public function utilisateursGetAction(Request $request): Response
    {
        $em = $this->em;
        $raw_content = $request->getContent();
        $sj = $this->sj;
        $su = $this->su;
        
        if ($raw_content === '' || $raw_content === '{}') {
            $content = null;
        } else {
            $content  = json_decode($request->getContent(), true);
        }
        if ($content === null) {
            $id_projet = null;
            $mail      = null;
        } else {
            $id_projet  = (isset($content['projet'])) ? $content['projet'] : null;
            $mail       = (isset($content['mail'])) ? $content['mail'] : null;
        }

        $users = [];
        $projets = [];

        // Tous les collaborateurs de tous les projets non terminés
        if ($id_projet === null && $mail === null) {
            $projets = $em->getRepository(Projet::class)->findNonTermines();
        }

        // Tous les projets dans lesquels une personne donnée a un login
        elseif ($id_projet === null) {
            $projets = $em->getRepository(Projet::class)->findNonTermines();
        }

        // Tous les collaborateurs d'un projet
        elseif ($mail === null) {
            $p = $em->getRepository(Projet::class)->find($id_projet);
            if ($p != null) {
                $projets[] = $p;
            }
        }

        // Un collaborateur particulier d'un projet particulier
        else {
            $p = $em->getRepository(Projet::class)->find($id_projet);
            if ($p->getEtatProjet() != Etat::TERMINE) {
                $projets[] = $p;
            }
        }

        //
        // Construire le tableau $users:
        //      toto@exemple.com => [ 'idIndividu' => 34, 'nom' => 'Toto', 'prenom' => 'Ernest', 'projets' => [ 'p0123' => 'toto', 'p456' => 'toto1' ] ]
        //
        //$pdbg=[];
        //foreach ($projets as $p) {
            //$pdbg[] = $p->getIdProjet();
        //};
        //return new Response(json_encode($pdbg));

        foreach ($projets as $p) {
            $id_projet = $p->getIdProjet();
            
            // On prend toutes les versions de chaque projet !
            $vs = [];
            $vs_labels = [];

            if ($p->getVersionDerniere() === null) {
                $this->sj->warningMessage("ATTENTION - Projet $p SANS DERNIERE VERSION !");
                continue;   // oups, projet bizarre
            } else {
                $vs[] = $p->getVersionDerniere();
                $vs_labels[] = 'derniere';
            }
            if ($p->getVersionActive() != null) {
                $vs[] = $p->getVersionActive();
                $vs_labels[] = 'active';
            }


            // $vs contient au moins une version
            $i = 0; // i=0 -> version dernière, $i=1 -> version active
            foreach ($vs as $v) {
                $collaborateurs = $v->getCollaborateurVersion();
                foreach ($collaborateurs as $cv)
                {
                    $m = $cv -> getCollaborateur() -> getMail();

                    // si on a spécifié un mail, ne retenir que celui-la
                    if ($mail != null && strtolower($mail) != strtolower($m))
                    {
                        continue;
                    }

                    // Pas de login demandé ni de login enregistré
                    //if ($cv->getLogin()==false && $cv->getClogin()==false && $cv->getLoginname()==null) {
                    //    continue;
                    //}

                    if (!isset($users[$m]))
                    {
                        $users[$m] = [];
                        $users[$m]['nom']        = $cv -> getCollaborateur() -> getNom();
                        $users[$m]['prenom']     = $cv -> getCollaborateur() -> getPrenom();
                        $users[$m]['idIndividu'] = $cv -> getCollaborateur() -> getIdIndividu();
                        $users[$m]['projets']    = [];
                    }

                    if ( isset($users[$m]['projets'][$id_projet]))
                    {
                        $prj_info = $users[$m]['projets'][$id_projet];
                    }
                    else
                    {
                        $prj_info = [];
                    }

                    // Les loginnames au niveau version
                    $loginnames = $su -> collaborateurVersion2LoginNames($cv);

                    // Au niveau projet = On prend si possible les loginnames de la dernière version
                    if (!isset($prj_info['loginnames']))
                    {
                        $prj_info['loginnames'] = $loginnames;
                    }
                    
                    $v_info = [];
                    $v_info['version'] = $v->getIdVersion();
                    
                    $v_info['loginnames'] = $loginnames;
                    $v_info['deleted'] = $cv->getDeleted();
                    
                    $prj_info[$vs_labels[$i]] = $v_info;

                    $users[$m]['projets'][$id_projet] = $prj_info;
                }
                $i += 1;
            }

        }

        // print_r est plus lisible pour le déboguage
        # return new Response(print_r($users,true));
        $sj -> infoMessage(__METHOD__ . " OK");
        return new Response(json_encode($users));
    }

    /**
     * get loginnames
     *
     * @Route("/getloginnames/{idProjet}/projet", name="get_loginnames", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    // curl --netrc -H "Content-Type: application/json" -X GET https://.../adminux/getloginnames/P1234/projet
    public function getloginnamesAction($idProjet): Response
    {
        $em = $this->em;
        $sj = $this->sj;
        $su = $this->su;
        
        $projet      = $em->getRepository(Projet::class)->find($idProjet);
        if ($projet === null) {
            $sj->infoMessage(__METHOD__ . " No projet $idProjet");
            return new Response(json_encode(['KO' => 'No Projet ' . $idProjet ]));
        }

        $versions    = $projet->getVersion();
        $output      =   [];
        $idProjet    =   $projet->getIdProjet();

        foreach ($versions as $version)
        {
            if ($version->getEtatVersion() == Etat::ACTIF)
            {
                foreach ($version->getCollaborateurVersion() as $cv)
                {
                    $collaborateur  = $cv->getCollaborateur() ;
                    if ($collaborateur !== null)
                    {
                        $prenom     = $collaborateur->getPrenom();
                        $nom        = $collaborateur->getNom();
                        $idIndividu = $collaborateur->getIdIndividu();
                        $mail       = $collaborateur->getMail();
                        $loginnames = $su->collaborateurVersion2LoginNames($cv, true);
                        $output[] =   [
                                'idIndividu' => $idIndividu,
                                'idProjet' =>$idProjet,
                                'mail' => $mail,
                                'prenom' => $prenom,
                                'nom' => $nom,
                                'loginnames' => $loginnames,
                        ];
                    }
                }
            }
        }

        $sj -> infoMessage(__METHOD__ . " OK");
        return new Response(json_encode($output));
    }

    /**
     * Vérifie la base de données, et envoie un mail si l'attribution d'un projet est différente du quota
     *
     * @Route("/quota_check", name="quota_check", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function quotaCheckAction(Request $request): Response
    {
        $grdt = $this->grdt;
        $sn = $this->sn;
        $sj = $this->sj;

        /****** SUPPRIME CAR PAS DE QUOTAS DANS CETTE VERSION *****/
        if (true)
        {
            // Envoi d'un message d'erreur
            $dest = $sn->mailUsers([ 'S' ], null);
            $msg = "\n ---->  FONCTIONNALITE NON IMPLEMENTEE !  <-----\n";
            $sn->sendMessage('notification/quota_check-sujet.html.twig', 'notification/quota_check-contenu.html.twig', [ 'MSG' => $msg ], $dest);
        }
        else
        {
            $annee_courante = $grdt->showYear();
            $sp      = $this->sp;
            $projets = $sp->projetsParAnnee($annee_courante)[0];
    
            // projets à problème
            $msg = "";
            foreach ($projets as $p) {
                // On ne s'occupe pas des projets terminés ou annulés
                // TODO - Tester sur l'état plutôt que sur le meta état,
                //        le méta état est censé être fait SEULEMENT pour l'affichage !
                if ( $p['metaetat'] === "TERMINE" ) continue;
                if ($p['attrib'] != $p['q']) {
                    $msg .= $p['p']->getIdProjet() . "\t" . $p['attrib'] . "\t\t" . $p["q"] . "\n";
                }
            }
    
            if ($msg != "") {
                $dest = $sn->mailUsers([ 'S' ], null);
                $sn->sendMessage('notification/quota_check-sujet.html.twig', 'notification/quota_check-contenu.html.twig', [ 'MSG' => $msg ], $dest);
            }
    
            $sj -> infoMessage(__METHOD__ . " OK");
        }
        return $this->render('consommation/conso_update_batch.html.twig');
    }

    /**
     * Vérifie la base de données, marque les mots de passe temporaires comme expirés
     * et renvoie les mots de passe cryptés (champ cpassword)
     * On pourra vérifier avec le mot de passe du supercalculateur et savoir s'il a été changé
     * Si le mot de passe est expiré, renvoie null
     *
     * @Route("/users/checkpassword", name="check_password", methods={"GET"})
     * @Route("/utilisateurs/checkpassword", name="check_password", methods={"GET"})
     *
     * curl --netrc -H "Content-Type: application/json" https://.../adminux/utilisateurs/checkpassword
     *
     */
    public function checkPasswordAction(Request $request): Response
    {
        $em = $this->em;
        $sj = $this->sj;
        $su = $this->su;
        
        $grdt = $this->grdt;
        $users = $em->getRepository(User::class)->findAll();
        $rusers = [];
        foreach ($users as $user)
        {
            $u = [];
            $u["loginname"] = $su->getLoginname($user);

            // Si pas le droit de travailler sur ce serveur, on passe
            if ( ! $this->checkUser($u["loginname"]))
            {
                continue;
            }
            
            // Si nécessaire on marque le user comme expiré, mais on ne supprime rien
            if ($user->getPassexpir() <= $grdt && $user->getExpire() === false)
            {
                $user->setExpire(true);
                $em->persist($user);
                $em->flush();
                
            }

            // On ne devrait jamais rentrer dans le if mais on ajoute de la robustesse
            if ($user->getPassexpir() > $grdt && $user->getExpire() === true)
            {
                $user->setExpire(false);
                $em->persist($user);
                $em->flush();
                
            }

            $u["loginname"] = $su->getLoginname($user);
            $u['expire'] = $user->getExpire();
            $rusers[] = $u;
        }

        $sj -> infoMessage(__METHOD__ . " OK");
        return new Response(json_encode($rusers));
    }

    /**
     * 
     * Vérifie que le user connecté a le droit d'intervenir sur ce serveur
     *
     * params = $loginname ou $serveur
     * return = true ssi le user connecté est admin sur le serveur 
     * 
     */
    private function checkUser(string|Serveur $prm ): bool
    {
        $token = $this->tok->getToken();
        $su = $this->su;
        $em = $this->em;
        
        if ( $prm instanceof Serveur)
        {
            $serveur = $prm;
        }
        else
        {
            $loginname_p = $su -> parseLoginname($prm);
            $serveur = $em->getRepository(Serveur::class)->findOneBy( ["nom" => $loginname_p['serveur']]);
            if ($serveur === null)
            {
               return false;
            }
        }

        // On vérifie que le user connecté est bien autorisé à agir sur ce serveur
        $moi = $token->getUser();
        if ($moi != null && $moi->getUserIdentifier() != $serveur->getAdmname())
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * get clessh
     *
     * @Route("/clessh/get", name="get_cles", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * Exemples de données POST (fmt json):
     *             '' -> toutes les clés
     *             ou
     *             '{ "rvk" : true }' -> Les clés qui ont été révoquées
     *             ou
     *             '{ "rvk" : false }' -> Les clés qui ne sont PAS révoquées
     *
     */
    // curl -s --netrc -H "Content-Type: application/json" -X POST -d '{}' https://.../adminux/clessh/get

    public function clesshGetAction(Request $request): Response
    {
        $em = $this->em;
        $raw_content = $request->getContent();
        $sj = $this->sj;
        $su = $this->su;
        
        if ($raw_content === '' || $raw_content === '{}')
        {
            $content = null;
        }
        else
        {
            $content  = json_decode($request->getContent(), true);
        }
        
        if ($content === null) {
            $rvk = 0;
        }
        else
        {
            if (isset($content['rvk']))
            {
                $rvk = $content['rvk'] ? 1:-1;
            }
            else
            {
                $rvk = 0;
            }
        }

        //
        // Construire le tableau $clessh:
        //      [ 'idCle' => 1, 'nom' => 'toto', 'pub' => 'rsa...', 'rvk' => false, 'users' => [ ['loginname' => 'toto', 'dply' => true] ]
        //

        $clessh = $em->getRepository(Clessh::class)->findall();
        $reponse = [];
        foreach ($clessh as $c)
        {
            $r_c = [];
            $r_c['idCle'] = $c->getId();
            $r_c['nom'] = $c->getNom();
            $r_c['pub'] = $c->getPub();
            $r_c['rvk'] = $c->getrvk();

            if ( $rvk === 1 && $r_c['rvk'] === false) continue;
            if ( $rvk === -1 && $r_c['rvk'] === true) continue;
            $r_c['idindividu'] = $c->getIndividu()->getIdIndividu();
            $r_c['empreinte'] = $c->getEmp();

            $users = $c->getUser();
            $r_users = [];
            foreach ($users as $u)
            {
                $r_u = [];
                //$c_cv = $u->getCollaborateurVersion();
                //if (empty($c_cv)) continue;   // oups ne devrait jamais arriver !
                //$l = count($c_cv);
                //$cv = $c_cv[$l-1];
                $individu = $u->getIndividu();
                if ($individu === null) continue; // oups ne devrait jamais arriver !
                $r_u['individu'] = $individu->getPrenom() . " " . $individu->getNom();
                $r_u['idIndividu'] = $individu->getIdIndividu();
                $r_u['mail'] = $individu->getMail();
                $r_u['loginname'] = $su->getLoginname($u);
                $r_u['deploy'] = $u->getDeply();
                $r_u['projet'] = $u->getProjet()->getIdProjet();
                $r_users[] = $r_u;
            }
            $r_c['users'] = $r_users;
            $reponse[] = $r_c;
        }
        $sj -> infoMessage(__METHOD__ . " OK");

        return new Response(json_encode($reponse));
    }

    /**
     * Déploie une clé ssh pour un utilisateur
     *
     * @Route("/clessh/deployer", name="deployer", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * Positionne à true le flag deploy du user, ce qui signifie que la clé ssh associée est déployée
     *
     */

    // curl --netrc -X POST -d '{ "loginname": "toto@TURPAN", "idIndividu": "6543", "projet": "P1234" }' https://.../adminux/clessh/deployer
    public function setdeplyAction(Request $request, LoggerInterface $lg): Response
    {
        $em = $this->em;
        $sj = $this->sj;
        $su = $this->su;

        $content  = json_decode($request->getContent(), true);
        if ($content === null) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de données");
            return new Response(json_encode(['KO' => 'Pas de données']));
        }
        if (empty($content['loginname'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de nom de login");
            return new Response(json_encode(['KO' => 'Pas de nom de login']));
        } else {
            $loginname = $content['loginname'];
        }
        if (empty($content['projet'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de projet");
            return new Response(json_encode(['KO' => 'Pas de projet']));
        } else {
            $idProjet = $content['projet'];
        }
        if (empty($content['idIndividu'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de idIndividu");
            return new Response(json_encode(['KO' => 'Pas de idIndividu']));
        } else {
            $idIndividu = $content['idIndividu'];
        }

        $error = [];
        $projet      = $em->getRepository(Projet::class)->find($idProjet);
        if ($projet === null) {
            $error[]    =   'No Projet ' . $idProjet;
        }

        $individu = $em->getRepository(Individu::class)->find($idIndividu);
        if ($individu === null) {
            $error[]    =   'No idIndividu ' . $idIndividu;
        }

        try
        {
            $loginname_p = $su -> parseLoginname($loginname);
        }
        catch (\Exception $e)
        {
            $error[] = "$loginname doit être de la forme 'alice@SERVEUR";
            $loginname_p = [];
            $loginname_p['serveur'] = '';
        }
        $serveur = $em->getRepository(Serveur::class)->findOneBy( ["nom" => $loginname_p['serveur']]);
        if ($serveur === null)
        {
           $error[] = 'No serveur ' . $loginname_p['serveur'];
        }

        // On vérifie que le user connecté est bien autorisé à agir sur ce serveur
        if ($serveur != null && ! $this->checkUser($serveur))
        {
           $error[] = 'ACCES INTERDIT A ' . $loginname_p['serveur']; 
        }

        if ($error != []) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - " . print_r($error, true));
            return new Response(json_encode(['KO' => $error ]));
        }

        $versions = $projet->getVersion();
        $i=0;
        foreach ($versions as $version) {
            // $version->getIdVersion()."\n";
            if ($version->getEtatVersion() === Etat::ACTIF             ||
                $version->getEtatVersion() === Etat::ACTIF_TEST        ||
                $version->getEtatVersion() === Etat::NOUVELLE_VERSION_DEMANDEE ||
                $version->getEtatVersion() === Etat::EN_ATTENTE
              )
              {
              foreach ($version->getCollaborateurVersion() as $cv)
              {
                  $collaborateur  =  $cv->getCollaborateur() ;
                  if ($collaborateur != null && $collaborateur->isEqualTo($individu)) {
                      $user = $em->getRepository(User::class)->findOneByLoginname($loginname);
                      if ($user === null)
                      {
                          $msg = "L'utilisateur $loginname n'existe pas";
                          $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - $msg");
                          return new Response(json_encode(['KO' => $msg]));
                      }
                      $user->setDeply(true);
                      Functions::sauvegarder($user,$em,$lg);
                      
                      $i += 1;
                      break; // Sortir de la boucle sur les cv
                  }
               }
            }
        }
        if ($i > 0 ) {
            $sj -> infoMessage(__METHOD__ . "$i versions modifiées");
            return new Response(json_encode(['OK' => "$i versions modifiees"]));
        } else {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Mauvais projet ou mauvais idIndividu !");
            return new Response(json_encode(['KO' => 'Mauvais projet ou mauvais idIndividu !' ]));
        }
    }

    /**
     * Révoque une clé ssh 
     *
     * @Route("/clessh/revoquer", name="revoquer", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * Positionne à true le flag rvk de la clé, ce qui signifie que la clé ssh est révoquée
     *
     */

    // curl --netrc -X POST -d '{ "idIndividu": "6543", "idCle": "55" }' https://.../adminux/clessh/revoquer
    public function setrvkAction(Request $request, LoggerInterface $lg): Response
    {
        $em = $this->em;
        $sj = $this->sj;
        $su = $this->su;

        $content  = json_decode($request->getContent(), true);
        if ($content === null) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de données");
            return new Response(json_encode(['KO' => 'Pas de données']));
        }
        if (empty($content['idCle'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de idCle");
            return new Response(json_encode(['KO' => 'Pas de clé']));
        } else {
            $idCle = $content['idCle'];
        }
        if (empty($content['idIndividu'])) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de idIndividu");
            return new Response(json_encode(['KO' => 'Pas de idIndividu']));
        } else {
            $idIndividu = $content['idIndividu'];
        }

        $error = [];

        $individu = $em->getRepository(Individu::class)->find($idIndividu);
        if ($individu === null) {
            $error[] = 'No idIndividu ' . $idIndividu;
        }

        $cle = $em->getRepository(Clessh::class)->find($idCle);
        if ($cle === null) {
            $error[] = 'No Cle ' . $idCle;
        }
        else
        {
            if ($cle->getIndividu()==null || $cle->getIndividu()->getIdIndividu() != $idIndividu)
            {
                $error[] = "La clé $idCle n'appartient pas à l'individu $idIndividu";
            }
        }

        if ($error != []) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - " . print_r($error, true));
            return new Response(json_encode(['KO' => $error ]));
        }

        $cle->setRvk(true);
        Functions::sauvegarder($cle,$em,$lg);
        return new Response(json_encode(['OK' => "clé $idCle révoquée"]));
    }
    
    /**
     * Exécution des tâches cron
     *
     * @Route("/cron/execute", name="cron_execute", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     */

    // curl --netrc -X GET https://.../adminux/cron/execute
    public function cronAction(Request $request): Response
    {
        $cr = $this->cr;
        $cr->execute();
        return new Response(json_encode(['OK' => '']));
    }

    /**
     *
     * Envoie la liste des choses à faire, en fonciotn de la valeur des flags 'todof'
     * 
     * @Route("/todo/get", name="todo_get", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     */
    // curl --netrc -X GET https://.../adminux/todo/get
    public function todoAction(Request $request): response
    {
        $em = $this->em;
        $sp = $this->sp;
        $sroc = $this->sroc;
        $sj = $this->sj;
        //$grdt = $this->grdt;
        $rep= $em->getRepository(Projet::class);

        $todo = [];
        $projets = $rep->findNonTermines();

        foreach ($projets as $p)
        {
            // TODO (ou pas) -> Projets à supprimer
            //               -> On ne les traite pas pour l'instant avec gramc-meso
            //               -> Il faudrait une table supplémentaire (pour les traiter par ressource)
            $v = $p->getVersionActive();
            if ($v === null) continue;

            // Y a-t-il une version à traiter ?
            $data = $this->__getTodo($v->getDac(),$v);
            if (count($data) !== 0)
            {
                $todo[] = $data;
                continue;
            }

            // Y a-t-il une rallonge à traiter ?
            // Attention on ne s'intéresse qu'aux ressources que le user connecté a le droit de traiter
            foreach ($v->getRallonge() as $r)
            {
                $data = $this->__getTodo($r->getDar(),$v,$r);
                if (count($data) !== 0)
                {
                    $todo[] = $data;
                }
            }
        }
        return new Response(json_encode($todo));
    }

    // Renvoie un tableau si on trouve un Dac/dar avec un todof à true
    // On ne regarde que les ressources accessibles par le user connecté
    private function __getTodo(\Doctrine\Common\Collections\Collection $dacdars, Version $v, Rallonge $r=null): array
    {
        $sroc = $this->sroc;
        
        foreach ($dacdars as $d)
        {
            $ressource = $d->getRessource();
            if ($ressource === null)
            {
                $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - $d - Ressource is null !");
                continue;
            }
            $serveur = $ressource->getServeur();
            if ($serveur === null)
            {
                $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - $d - Serveur is null !");
                continue;
            }
            if ( ! $this->checkUser($serveur)) continue;

            $data = [];
            if ($d->getTodof())
            {
                $data['action'] = 'attribution';
                $data['idProjet'] = $v->getProjet()->getIdProjet();
                if ($r !== null)
                {
                    $data['idRallonge'] = $r->getIdRallonge();
                }
                $data['attribution'] = $d->getAttribution();
                $data['ressource'] = $sroc->getNomComplet($d->getRessource());
                break;
            }
        }
        return $data;
    }

    /**
     * Signale qu'une action a été réalisée
     *
     * @Route("/todo/done", name="todo_done", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * Positionne à false le flag todof du projet, ou des dar ou des dac
     *
     */
    // curl --netrc -X POST -d '{ "projet": "M12345", "ressource": "TURPAN" }' https://.../adminux/todo/done
    public function setdoneAction(Request $request): Response
    {
        $em = $this->em;
        $sj = $this->sj;
        $su = $this->su;
        $sroc = $this->sroc;

        $content  = json_decode($request->getContent(), true);
        if ($content === null) {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de données");
            return new Response(json_encode(['KO' => 'Pas de données']));
        }
        if (empty($content['projet']))
        {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de projet");
            return new Response(json_encode(['KO' => 'Pas de nom projet']));
        }
        else
        {
            $idProjet = $content['projet'];
        }
        if (empty($content['ressource']))
        {
            $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de ressource");
            return new Response(json_encode(['KO' => 'Pas de ressource']));
        }
        else
        {
            $nomRessource = $content['ressource'];
        }

        // Pas de requêtes actuellement pour trouver une ressource avec son nom complet
        // Donc on balaie toutes les ressources... qui ne devraient pas être super-nombreuses non plus
        $error = [];
        $ressources = $sroc->getRessources();
        $ressource = null;
        foreach ($ressources as $r)
        {
            if ($sroc->getnomComplet($r) === $nomRessource)
            {
                $ressource = $r;
                break;
            }
        }

        if ($ressource === null)
        {
            $error[] = 'No ressource ' . $nomRessource;
            $serveur = null;
        }
        else
        {
            $serveur = $ressource->getServeur();
        }

        // On vérifie que le user connecté est bien autorisé à agir sur le serveur de cette ressource
        if ($serveur !== null && ! $this->checkUser($serveur))
        {
           $error[] = 'ACCES INTERDIT A ' . $nomRessource; 
        }

        $projet = $em->getRepository(Projet::class)->find($idProjet);
        if ($projet === null)
        {
            $error[]    =   'No Projet ' . $idProjet;
        }
        else
        {
            $version = $projet->getVersionActive();
        }

        if (count($error) === 0)
        {
            // La version doit-elle être acquittée ?
            $todofound = $this->__clrTodof($version->getDac(), $ressource);

            // Sinon, existe-t-il une rallonge non encore acquittée ?
            if (! $todofound)
            {
                foreach ($version->getRallonge() as $r)
                {
                    $todofound = $this->__clrTodof($r->getDar(), $ressource);
                    if ($todofound) break;
                }
            }

            // Erreur, il n'y a rien à acquitter !
            if (! $todofound)
            {
                $error[] = "Pas de todo-flag sur le projet $idProjet pour la ressource $nomRessource";
            }
        }
        
        if ($error != []) {
            $sj->errorMessage(print_r($error, true));
            return new Response(json_encode(['KO' => $error ]));
        }
        else
        {
            return new Response(json_encode(['OK']));
        }
    }

    // Si un des dac/dar a son todof true, on le met à false et on renvoie true
    private function __clrTodof(\Doctrine\Common\Collections\Collection $dacdars, Ressource $ressource): bool
    {
        $em = $this->em;
        foreach ($dacdars as $d)
        {
            if ($d->getRessource() === $ressource)
            {
                if ($d->getTodof())
                {
                    $d->setTodof(false);
                    $em->persist($d);
                    $em->flush();
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * SEULEMENT EN DEBUG: renvoie la date
     *
     * @Route("/gramcdate/get", name="grdt_get", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * Renvoie la date d'après gramc
     *
     */

    // curl --netrc -X GET https://.../adminux/gramcdate/get
    public function getDateAction(Request $request): Response
    {
        if ($this->getParameter('kernel.debug') === false)
        {
            return new Response(json_encode(['KO' => 'Seulement en debug !']));
        }
        else
        {
            return new Response(json_encode($this->grdt->format('Y-m-d')));
        }
    }

    /**
     * get adresses IP
     *
     * @Route("/adresseip/get", name="get_adresseip", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Exemples de données POST (fmt json):
     *             ''
     *             ou
     *             '{ "labo" : true     }' -> Toutes les adresses IP, associées à un acronyme de laboratoire
     *             ou
     *             '{ "verif" : true }' --> Si true, ne renvoie que les adresses utiles au mésocentre connecté
     *
     */
    // curl --netrc -H "Content-Type: application/json" -X POST -d '{ "labo": true }' https://.../adminux/adresseip/get

    public function adresseipGetAction(Request $request): Response
    {
        $em = $this->em;
        $sp = $this->sp;
        $sj = $this->sj;
        $token = $this->tok->getToken();
        $grdt = $this->grdt;
        $labo_rep= $em->getRepository(Laboratoire::class);

        $content  = json_decode($request->getContent(), true);
        //print_r($content);
        if ($content === null) {
            $labo = false;
            $verif = false;
        }
        else
        {
            $labo = (isset($content['labo'])) ? $content['labo'] : false;
            $verif = (isset($content['verif']))? $content['verif']: false;
        }

        if ($verif === false)
        {
            $labos = $labo_rep->findAll();
        }
        else
        {
            $moi = $token->getUser();
            if ($moi === null)
            {
                $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - non connecté");
                return new Response(json_encode(['KO' => 'Erreur interne']));
            }
            $labos = $labo_rep->findByAdmname($moi->getUserIdentifier());
        }
            

        $adresses = [];

        if ($labo === true)
        {
            foreach ($labos as $l)
            {
                $adr_lab = [];
                foreach ($l->getAdresseip() as $adr)
                {
                    $adr_lab[] = $adr->getAdresse();
                }
                $adresses[$l->getAcroLabo()] = $adr_lab;
            }
        }
        else
        {
            foreach ($labos as $l)
            {
                foreach ($l->getAdresseip() as $adr)
                {
                    if (! in_array($adr->getAdresse(),$adresses))
                    {
                        $adresses[] = $adr->getAdresse();
                    }
                }
            }
        }
        
        $sj -> infoMessage(__METHOD__ . " OK");
        return new Response(json_encode($adresses));
    }
    
    /**
     * SEULEMENT EN DEBUG: modifie la date
     *
     * @Route("/gramcdate/set", name="grdt_set", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * shift: Modifie la gramcdate en ajoutant d jours à la date d'aujourd'hui
     *        Si d vaut today, revient à la date d'aujourd'hui
     * rel:   Si rel vaut true, le shift est calculé par rapport à la gramcdate actuellement positionnée.
     * 
     * Si le paramètre "cron" est spécifié, le service cron est appelé après le changement de date
     * (attention on ne peut pas appeler "cron" avec "shift" : "today")
     *
     */

    // curl --netrc -X POST -d '{ "shift": "2" }' https://.../adminux/gramcdate/set
    // curl --netrc -X POST -d '{ "rel" : true, shift": "2" }' https://.../adminux/gramcdate/set
    // curl --netrc -X POST -d '{ "shift": "today" }' https://.../adminux/gramcdate/set
    // curl --netrc -X POST -d '{ "shift": "2", "cron":"1" }' https://.../adminux/gramcdate/set

    public function setDateAction(Request $request): Response
    {
        $grdt = $this->grdt;
        $cr = $this->cr;
        $sj = $this->sj;
        $em = $this->em;
        
        if ($this->getParameter('kernel.debug') === false)
        {
            return new Response(json_encode(['KO' => 'Seulement en debug !']));
        }
        else
        {
            $shift = 0;
            $rel = false;
            $cron = false;
            $content  = json_decode($request->getContent(), true);
            if ($content === null)
            {
                $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de données");
                return new Response(json_encode(['KO' => 'Pas de données']));
            }
            if (empty($content['shift']))
            {
                $sj->errorMessage(__METHOD__ . ':' . __FILE__ . " - Pas de shift");
                return new Response(json_encode(['KO' => 'Pas de shift']));
            }
            else
            {
                $shift = $content['shift'];
            }
            if (!empty($content['cron']))
            {
                $cron = boolval($content['cron']);
            }
            if (!empty($content['rel']))
            {
                $rel = boolval($content['rel']);
            }
            
            $grdt_now = $em->getRepository(Param::class)->findOneBy(['cle' => 'now']);
            if ($grdt_now === null)
            {
                $grdt_now = new Param();
                $grdt_now->setCle('now');
            }

            if ($shift === 'today')
            {
                $em->remove($grdt_now);
            }
            else
            {
                $shift = intval($shift);
                if ($shift <= 0)
                {
                    return new Response(json_encode(['KO' => 'shift vaut soit un entier positif soit "today"']));
                }

                if ($rel)
                {
                    $date = $grdt->getNew();
                }
                else
                {
                    $date = new \DateTime();

                }
                $dateInterval = new \DateInterval('P'.$shift.'D');
                $date -> add($dateInterval);
                $grdt_now->setVal($date->format("Y-m-d"));
                $em->persist($grdt_now);
            }
            $em->flush();

            // On relit le paramètre pour actualiser le cache de Doctrine
            $grdt_now = $em->getRepository(Param::class)->findOneBy(['cle' => 'now']);

            // Exécuter le cron si demandé
            if ($cron)
            {
                $cr->execute();
            }

            return new Response(json_encode(['OK' => '']));
        }
    }
}
