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

namespace App\Repository;

use App\Entity\Version;
use App\Entity\Projet;
use App\GramcServices\Etat;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CollaborateurversionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
//class CollaborateurVersionRepository extends ServiceEntityRepository
class CollaborateurVersionRepository extends \Doctrine\ORM\EntityRepository
{
//    public function __construct(
//        ManagerRegistry $registry,
//    )
//    {
//        parent::__construct($registry, CollaborateurVersion::class);
//    }

    public function getResponsable($projet)
    {
        $collaborateurVersion = $this->getEntityManager()
         ->createQuery('SELECT partial v.{id}  FROM App:CollaborateurVersion v JOIN App:Projet p WHERE ( v.responsable = true AND  v.version = p.versionDerniere AND p = :projet)')

        ->setParameter('projet', $projet)
        ->getOneOrNullResult();

        if ($collaborateurVersion != null) {
            return $collaborateurVersion->getCollaborateur();
        } else {
            return null;
        }
    }

    /*
     * Renvoie les collaborateurs d'un projet sous forme de tableau associatif:
     *      $idIndividu => $individu
     */
    public function getCollaborateurs($projet)
    {
        $output = $this->getEntityManager()
         ->createQuery('SELECT i  FROM App:Individu i, App:CollaborateurVersion cv JOIN cv.version v JOIN'
        .' v.projet p WHERE ( cv.collaborateur = i AND p = :projet AND NOT v.etatVersion = :termine AND NOT v.etatVersion = :annule)')

        ->setParameter('projet', $projet)
        ->setParameter('termine', Etat::getEtat('TERMINE'))
        ->setParameter('annule', Etat::getEtat('ANNULE'))
        ->getResult();

        $collaborateurs =   [];
        foreach ($output as $user) {
            $collaborateurs[ $user->getIdIndividu() ] = $user;
        }

        return $collaborateurs;
    }
}
