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

namespace App\GramcServices;

class Etat
{
    // etats
    public const INVALIDE                  = 0;
    public const CREE_ATTENTE              = 1;
    public const EDITION_DEMANDE           = 2;
    public const EDITION_EXPERTISE         = 3;
    public const EN_ATTENTE                = 4;
    public const ACTIF                     = 5;
    public const NOUVELLE_VERSION_DEMANDEE = 6;
    public const ACTIF_R                   = 7;
    public const STANDBY                   = 8;
    public const TERMINE                   = 9;
    public const ANNULE                    = 10;
    public const REFUSE                    = 11;
    public const FIN_ETATS                 = 12;

    public const EDITION_TEST              = 21;
    public const EXPERTISE_TEST            = 22;
    public const ACTIF_TEST                = 23;

    public const DESAFFECTE                = 31;

    public const RENOUVELABLE              = 41;
    public const NON_RENOUVELABLE          = 42;

    public const J_99                      = 899;
    public const J_30                      = 830;
    public const J_15                      = 815;
    public const J_7                       = 807;
    public const J_1                       = 801;

    public const   LIBELLE_ETAT=
        [
            self::INVALIDE                  =>  'INVALIDE',
            self::CREE_ATTENTE              =>  'CREE_ATTENTE',
            self::EDITION_DEMANDE           =>  'EDITION_DEMANDE',
            self::EDITION_EXPERTISE         =>  'EDITION_EXPERTISE',
            self::EN_ATTENTE                =>  'EN_ATTENTE',
            self::ACTIF                     =>  'ACTIF',
            self::NOUVELLE_VERSION_DEMANDEE =>  'NOUVELLE_VERSION_DEMANDEE',
            self::ACTIF_R                   =>  'ACTIF_R',
            self::STANDBY                   =>  'STANDBY',
            self::TERMINE                   =>  'TERMINE',
            self::ANNULE                    =>  'ANNULE',
            self::REFUSE                    =>  'REFUSE',
            self::FIN_ETATS                 =>  'FIN_ETATS',
            self::EDITION_TEST              =>  'EDITION_TEST',
            self::EXPERTISE_TEST            =>  'EXPERTISE_TEST',
            self::ACTIF_TEST                =>  'ACTIF_TEST',
            self::DESAFFECTE                =>  'DESAFFECTE',
            self::RENOUVELABLE              =>  'RENOUVELABLE',
            self::NON_RENOUVELABLE          =>  'NON_RENOUVELABLE',
        ];

    // Pour utiliser avec cmpEtatExpertise ou cmpEtatExpertiseRall
    // L'ordre sera d'abord les projets en expertise ou en attente, PUIS les projets actifs
    public const ORDRE_ETAT      = [ 10, 10, 2, 0, 4, 5, 6, 7, 8, 9, 10 ];
    public const ORDRE_ETAT_RALL = [ 10, 10, 3, 2, 0, 4, 10, 10, 10, 9, 10 ];
    
    public static function getLibelle($etat): string
    {
        if ($etat != null && array_key_exists($etat, Etat::LIBELLE_ETAT)) {
            return self::LIBELLE_ETAT[$etat];
        } else {
            return 'UNKNOWN';
        }
    }

    public static function getEtat($libelle): int
    {
        $array_flip = array_flip(self::LIBELLE_ETAT);

        if ($libelle != null && array_key_exists($libelle, $array_flip)) {
            return $array_flip[$libelle];
        } else {
            return null;
        }
    }

    // Compare les états en mettant en PREMIER les états utiles pour affectation des experts
    // NOTE - Résultat indéfini si $a ou $b sont négatifs !
    // TODO - Refaire ça comme cmpEtatExpertiseRall
    public static function cmpEtatExpertise(int $a, int $b): int
    {
        if ($a == $b) return 0;
        return (self::ORDRE_ETAT[$a] < self::ORDRE_ETAT_RALL[$b]) ? -1 : 1;
    }

    // La seule différence est l'ordre des états
    public static function cmpEtatExpertiseRall(int $a, int $b): int
    {
        if ($a == $b) return 0;
        return (self::ORDRE_ETAT_RALL[$a] < self::ORDRE_ETAT_RALL[$b]) ? -1 : 1;
    }
}
