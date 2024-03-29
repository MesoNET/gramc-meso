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

class Signal
{
    // signaux
    public const INCONNU          = 0;
    public const CLK_DEMANDE      = 1;
    public const DAT_DEB_DEM      = 2;
    public const DAT_FIN_DEM      = 3;
    public const CLK_ATTR_PRS     = 5;

    public const CLK_VAL_DEM      = 10;
    public const CLK_VAL_EXP_OK   = 11;
    public const CLK_VAL_EXP_KO   = 12;
    public const CLK_VAL_EXP_CONT = 13; // ni OK ni KO pour une session B: 0 heures mais on continue l'an prochain
    public const CLK_ARR          = 14;
    public const CLK_VAL_PRS      = 15;

    public const CLK_SESS_DEB     = 20;
    public const CLK_SESS_FIN     = 21;
    public const CLK_FERM         = 22;

    public const DAT_ACTR         = 30;
    public const DAT_CAL_99       = 899;
    public const DAT_CAL_30       = 830;
    public const DAT_CAL_15       = 815;
    public const DAT_CAL_7        = 807;
    public const DAT_CAL_1        = 801;
    public const DAT_CAL_0        = 800;
    public const FIN_EVENEMENTS   = 32;

    // nouveaux signaux
    // const FERMER_RALLONGE       = 40;
    // const CLK_VAL_EXP_OK_RETARD = 41;
    public const CLK_TEST         = 42;
    public const CLK_ERASE        = 43;

    // signaux rallonge
    public const CLK_AFFECTER     = 50;
    public const CLK_DESAFFECTER  = 51;

    public const   LIBELLE_SIGNAL  =
    [
        self::INCONNU          => 'INCONNU',
        self::CLK_DEMANDE      => 'CLK_DEMANDE',
        self::DAT_DEB_DEM      => 'DAT_DEB_DEM',
        self::DAT_FIN_DEM      => 'DAT_FIN_DEM',
        self::CLK_VAL_DEM      => 'CLK_VAL_DEM',
        self::CLK_ATTR_PRS     => 'CLK_ATTR_PRS',
        self::CLK_VAL_EXP_OK   => 'CLK_VAL_EXP_OK',
        self::CLK_VAL_EXP_KO   => 'CLK_VAL_EXP_KO',
        self::CLK_VAL_EXP_CONT => 'CLK_VAL_EXP_CONT',
        self::CLK_VAL_PRS      => 'CLK_VAL_PRS',
        self::CLK_SESS_DEB     => 'CLK_SESS_DEB',
        self::CLK_SESS_FIN     => 'CLK_SESS_FIN',
        self::CLK_FERM         => 'CLK_FERM',
        self::CLK_ARR          => 'CLK_ARR',
        self::DAT_ACTR         => 'DAT_ACTR',
        self::FIN_EVENEMENTS   => 'FIN_EVENEMENTS',

        self::CLK_TEST         =>  'CLK_TEST',
        //self::CLK_ERASE        =>  'CLK_ERASE',

        self::CLK_AFFECTER     =>  'CLK_AFFECTER',
        self::CLK_DESAFFECTER  =>  'CLK_DESAFFECTER',

    ];

    public static function getLibelle($signal)
    {
        if ($signal != null && array_key_exists($signal, static::LIBELLE_SIGNAL)) {
            return static::LIBELLE_SIGNAL[$signal];
        } else {
            return 'UNKNOWN(' . $signal . ')';
        }
    }

    public static function getSignal($libelle)
    {
        $array_flip = array_flip(static::LIBELLE_SIGNAL);

        if ($libelle != null && array_key_exists($libelle, $array_flip)) {
            return $array_flip[$libelle];
        } else {
            return null;
        }
    }
}
