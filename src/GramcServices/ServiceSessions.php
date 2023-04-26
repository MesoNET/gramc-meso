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

use App\Entity\Session;
use App\Utils\Functions;
use App\GramcServices\Etat;
use App\GramcServices\GramcDate;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;


class ServiceSessions
{
    public function __construct(
        private GramcDate $grdt,
        private FormFactoryInterface $ff,
        private EntityManagerInterface $em
    ) {}

    /************
     * Formulaire permettant de choisir une année
     *
     * $request = La requête
     * $annee   = L'année, si null on prend l'année courante
     *
     * Retourne un tableau contenant:
     *     Le formulaire
     *     L'année choisie
     *
     * Utilisation depuis un contrôleur:
     *             $data = $ss->selectAnnee($request);
     *
     * TODO = Ne pas utiliser Functions::createFormBuilder
     *        Donner un nom au formulaire
     *
     *******************/

    public function selectAnnee(Request $request, $annee = null): array
    {
        $grdt = $this->grdt;
        
        $annee_max = new \DateTime($grdt->showYear().'-01-01');
        $annee_min = new \DateTime('2023-01-01'); // Mesonet commence en 2023 
        if ($annee === null) {
            $annee = $annee_max->format('Y');
        }

        $choices = array_reverse(Functions::choicesYear($annee_min, $annee_max, 0), true);
        $form    = Functions::createFormBuilder($this->ff, ['annee' => $annee ])
                    ->add(
                        'annee',
                        ChoiceType::class,
                        [
                            'multiple' => false,
                            'required' =>  true,
                            'label'    => '',
                            'choices'  => $choices,
                        ]
                    )
                    ->add('submit', SubmitType::class, ['label' => 'Choisir'])
                    ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $annee = $form->getData()['annee'];
        }

        return ['form'  =>  $form, 'annee'    => $annee ];
    }
}
