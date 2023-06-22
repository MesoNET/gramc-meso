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

namespace App\GramcServices;

use App\GramcServices\GramcDate;
use App\GramcServices\Etat;
use App\Entity\Session;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;

const VERSION = "0.6.5";

/*
 * Cette classe garde des informations pouvant être reprises par
 * les autres objets, et en particulier par les pages twig (haut et bas de page)
 *
 ******/
class ServiceInfos
{
    public function __construct(private GramcDate $grdte, private EntityManagerInterface $em) {}

    public function mail_replace($mail): string
    {
        return str_replace('@', ' at ', $mail);
    }

    public function gramc_date($format): GramcDate|string
    {
        $d = $this->grdte;
        if ($format === 'raw')
        {
            return $d;
        }
        else
        {
            return $d->format($format);
        }
    }

    // TODO - strftime est obsolète à partir de php 8.1 !
    public function strftime_fr($format, $date): string
    {
        setlocale(LC_TIME, 'fr_FR.UTF-8');
        return strftime($format, $date->getTimestamp());
    } // function strftime_fr


    public function tronquer_chaine(?string $s, string|int $l): ?string
    {
        if (grapheme_strlen($s)>=intval($l))
        {
            return grapheme_substr($s, 0, intval($l)).'...';
        }
        else
        {
            return $s;
        }
    }

    public function getVersion(): string
    {
        return VERSION;
    }
} // class
