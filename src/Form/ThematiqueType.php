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

use App\Entity\Individu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
// use App\App;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ThematiqueType extends AbstractType
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelleThematique')
            ->add(
                'expert',
                EntityType::class,
                [
                'multiple' => true,
                'expanded' => true,
                'class' => Individu::class,
                'choices' => $options['experts'],
                ]
            );

        if (true == $options['modifier']) {
            $builder
                ->add('submit', SubmitType::class, ['label' => 'modifier'])
                ->add('reset', ResetType::class, ['label' => 'reset']);
        } elseif (true == $options['ajouter']) {
            $builder
                ->add('submit', SubmitType::class, ['label' => 'ajouter'])
                ->add('reset', ResetType::class, ['label' => 'reset']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
            'data_class' => 'App\Entity\Thematique',
            'modifier' => false,
            'ajouter' => false,
            'experts' => $this->em->getRepository(Individu::class)->findAll(),
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'appbundle_thematique';
    }
}
