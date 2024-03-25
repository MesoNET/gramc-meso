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
 *  authors : Miloslav Grundmann - C.N.R.S. - UMS 3667 - CALMIP
 *            Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace App\Form;

use App\GramcServices\GramcDate;
use App\Utils\Functions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublicationType extends AbstractType
{
    private $grdt;

    public function __construct(GramcDate $grdt)
    {
        $this->grdt = $grdt;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('refbib', TextType::class, ['required' => true])
            ->add('doi', TextType::class, ['required' => false])
            ->add('openUrl', TextType::class, ['required' => false])
            ->add('idPubli', IntegerType::class, ['required' => false])
            ->add(
                'annee',
                ChoiceType::class,
                [
                    'choices' => Functions::choicesYear(new \DateTime('2000-01-01'), $this->grdt, 0),
                ]
            );

        if (true == $options['projet']) {
            $builder->add('projet');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => 'App\Entity\Publication',
                'projet' => false,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'appbundle_publication';
    }
}
