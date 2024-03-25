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

use App\Entity\Serveur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RessourceType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('serveur', EntityType::class,
                [
                    'label' => 'Serveur:',
                    'class' => Serveur::class,
                    'multiple' => false,
                    'placeholder' => '-- Serveur',
                    'required' => true,
                    'choices' => $this->em->getRepository(Serveur::class)->findAllSorted(),
                    'attr' => ['style' => 'width:20em'],
                ]
            )
            ->add('nom', TextType::class, ['required' => false, 'label' => 'nom de la ressource (optionnel, 8 char max):', 'attr' => ['maxlength' => 8]])
            ->add('desc', TextareaType::class, ['required' => true, 'label' => 'Description (balises html ok):', 'attr' => ['rows' => '5', 'cols' => '50']])
            ->add('docUrl', TextType::class, ['required' => false, 'label' => 'URL vers la doc :', 'attr' => ['size' => '40']])
            ->add('unite', TextType::class, ['required' => false, 'label' => 'Unité :'])
            ->add('maxDem', IntegerType::class, ['required' => false, 'label' => 'Valeur max de la demande :', 'attr' => ['min' => 0]])
            ->add('co2', IntegerType::class, ['required' => false, 'label' => 'co2 (g) émis par unite et par heure :', 'attr' => ['min' => 0]]);

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
                'data_class' => 'App\Entity\Ressource',
                'modifier' => false,
                'ajouter' => false,
            ]
        );
    }
}
