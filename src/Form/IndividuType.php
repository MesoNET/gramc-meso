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

use App\Entity\Etablissement;
use App\Entity\Laboratoire;
use App\Entity\Statut;
use App\Entity\Thematique;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IndividuType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (true == $options['admin']) {
            $builder->add('creationStamp');
        }

        if (true == $options['user']) {
            $builder
                ->add('nom', TextType::class, ['label' => 'Nom:'])
                ->add('prenom', TextType::class, ['label' => 'Prénom']);
            if (true == $options['mail']) {
                $builder->add('mail', EmailType::class);
            } else {
                $builder->add('mail', EmailType::class, ['disabled' => true]);
            }

            if (true == $options['mail']) {
                $builder->add('mail', EmailType::class);
            } else {
                $builder->add('mail', EmailType::class, ['disabled' => true]);
            }
        }

        if (true == $options['admin']) {
            $builder
                ->add('admin')
                ->add('expert')
                ->add('responsable')
                ->add('collaborateur')
                ->add('president')
                ->add('desactive');
        }

        if (true == $options['user']) {
            $builder
                ->add(
                    'labo',
                    EntityType::class,
                    [
                    'label' => 'Laboratoire:',
                    'class' => Laboratoire::class,
                    'multiple' => false,
                    'placeholder' => '-- Indiquez le laboratoire',
                    'required' => false,
                    'choices' => $this->em->getRepository(Laboratoire::class)->findAllSorted(),
                    'attr' => ['style' => 'width:20em'],
                    ]
                );
        }

        if (true == $options['permanent']) {
            $builder
                ->add(
                    'statut',
                    EntityType::class,
                    [
                    'placeholder' => '-- Indiquez votre statut',
                    'label' => 'Statut:',
                    'class' => Statut::class,
                    'multiple' => false,
                    'required' => false,
                    'choices' => $this->em->getRepository(Statut::class)->findBy(['permanent' => true]),
                    'attr' => ['style' => 'width:20em'],
                    ]
                );
        } else {
            $builder
                ->add(
                    'statut',
                    EntityType::class,
                    [
                    'placeholder' => '-- Indiquez votre statut',
                    'label' => 'Statut:',
                    'class' => Statut::class,
                    'multiple' => false,
                    'required' => false,
                    'attr' => ['style' => 'width:20em'],
                    ]
                );
        }

        $builder
            ->add(
                'etab',
                EntityType::class,
                [
                    'placeholder' => '-- Indiquez votre établissement',
                    'label' => 'Établissement:',
                    'class' => Etablissement::class,
                    'multiple' => false,
                    'required' => false,
                    'attr' => ['style' => 'width:20em'],
                    ]
            );

        if (true == $options['thematique']) {
            $builder->add(
                'thematique',
                EntityType::class,
                [
                'multiple' => true,
                'expanded' => true,
                'class' => Thematique::class,
                ]
            );
        }

        if (true == $options['submit']) {
            $builder
                ->add(
                    'submit',
                    SubmitType::class,
                    [
                    'label' => 'Valider',
                    'attr' => ['style' => 'width:10em'],
                    ]
                );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
            'data_class' => 'App\Entity\Individu',
            'admin' => false,
            'user' => true,
            'submit' => true,
            'thematique' => false,
            'permanent' => false,
            'mail' => true,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'appbundle_individu';
    }
}
