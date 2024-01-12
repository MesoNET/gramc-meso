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
use App\Entity\Individu;
use App\Entity\Projet;
use App\Entity\Serveur;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/*************************************************************
 * Quelques fonctions utiles pour la table User
 *
 *************************************************************/
class ServiceUsers
{
    public function __construct(private ServiceServeurs $sr, private EntityManagerInterface $em)
    {
    }

    /******************************************
     * Renvoie la liste des loginnames pour un CollaborateurVersion donné
     * sous forme de tableau associatif:
     *      $s['TURPAN']['nom'] -> le nom de login (toto), ou 'nologin'
     *                             si $long=true, renvoie toto@TURPAN
     *      $s['TURPAN']['clessh'] -> la cléssh, ou null
     *      $s['TURPAN']['userid] -> le id du user
     *      $s['TURPAN']['deploy'] -> le flag deply (clé déployée ou pas)
     *************************************/
    public function collaborateurVersion2LoginNames(CollaborateurVersion $cv = null, bool $long = false): array
    {
        $em = $this->em;
        $sr = $this->sr;

        $serveurs = $sr->getServeurs();
        $loginnames3 = [];
        if (null != $cv) {
            foreach ($serveurs as $s) {
                $u = $this->getUser($cv->getCollaborateur(), $cv->getVersion()->getProjet(), $s);
                $sn = $s->getNom();

                $loginnames3[$sn]['nom'] = $u->getLoginname() ? $u->getLoginname() : 'nologin';
                $loginnames3[$sn]['login'] = $u->getLogin();
                if ($long && 'nologin' != $loginnames3[$sn]['nom']) {
                    $loginnames3[$sn]['nom'] .= '@'.$sn;
                }
                $clessh = $u->getClessh();
                if (null === $clessh) {
                    $loginnames3[$sn]['clessh'] = null;
                } else {
                    $loginnames3[$sn]['clessh']['idCle'] = $u->getClessh()->getId();
                    $loginnames3[$sn]['clessh']['nom'] = $u->getClessh()->getNom();
                    $loginnames3[$sn]['clessh']['pub'] = $u->getClessh()->getPub();
                    $loginnames3[$sn]['clessh']['rvk'] = $u->getClessh()->getRvk();
                    $loginnames3[$sn]['clessh']['deploy'] = $u->getDeply();
                }
                $loginnames3[$sn]['userid'] = $u->getId();
            }
        } else {
            foreach ($serveurs as $s) {
                $sn = $s->getNom();
                $loginnames3[$sn]['nom'] = 'nologin';
                $loginnames3[$sn]['login'] = false;
                $loginnames3[$sn]['clessh'] = null;
            }
        }

        // dd($loginnames3);
        return $loginnames3;
    }

    /*******************************************************
     * Renvoie le loginname sous la forme alice@serveur
     ******************************************************/
    public function getLoginname(User $u): string
    {
        return $u->getLoginname().'@'.$u->getServeur();
    }

    /************************************************
     * Renvoie le couple 'user', 'serveur' pour un user alice@example.com
     *********************************************************************/
    public function parseLoginname(string $u): array
    {
        $rvl = explode('@', $u, 2);
        if (2 != count($rvl)) {
            throw new \Exception(__METHOD__.':'.__LINE__." $u n'est pas de la forme alice@serveur");
        }

        return ['loginname' => $rvl[0], 'serveur' => $rvl[1]];
    }

    /*********************************************************
     * Renvoie UN user et UN SEUL. Si le user n'existe pas on le crée
     ***********************************************************/
    public function getUser(Individu $i, Projet $p, Serveur $s): User
    {
        $em = $this->em;
        $users = $em->getRepository(User::class)->findBy(['individu' => $i, 'projet' => $p, 'serveur' => $s]);
        if (0 == count($users)) {
            $u = new User();
            $u->setIndividu($i);
            $u->setProjet($p);
            $u->setServeur($s);
            $p->addUser($u);
            $em->persist($u);
            $em->flush($u);
        } elseif (1 == count($users)) {
            $u = $users[0];
        } else {
            throw $this->sj->throwException('ServiceUsers:getUser findBy renvoie '.count($users).' objets '."$i - $p - $s");
        }

        return $u;
    }
} // class
