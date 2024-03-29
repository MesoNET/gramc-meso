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

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\DateType;


class FormationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numeroform', IntegerType::class, ['required' => true, 'label' => "Numéro d'ordre *:" ])
            ->add('acroform', TextType::class, ['required' => true, 'label' => 'Acronyme *:' ])
            ->add('nomform', TextType::class, ['required' => true, 'label' => 'Nom *:' ])
            ->add('startdate', DateType::class, ['required' => true, 'label' => 'Ne pas proposer avant cette date *:', 'widget' => 'single_text' ])
            ->add('enddate', DateType::class, ['required' => true, 'label' => 'Ne pas proposer après cette date *:', 'widget' => 'single_text' ]);

        if ($options['modifier'] == false &&  $options['ajouter'] == false)
        {
            $builder->add('individu');
        }

        if ($options['modifier'] == true)
        {
            $builder
                ->add('submit', SubmitType::class, ['label' => 'modifier' ])
                ->add('reset', ResetType::class, ['label' => 'reset' ]);
        }
        elseif ($options['ajouter'] == true)
        {
            $builder
                ->add('submit', SubmitType::class, ['label' => 'ajouter' ])
                ->add('reset', ResetType::class, ['label' => 'reset' ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
            'data_class' => 'App\Entity\Formation',
            'modifier' => false,
            'ajouter'  => false,
            ]
        );
    }
}
