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
use App\Form\IndividuForm\IndividuForm;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// use Symfony\Component\Form\FormInterface;

// use Symfony\Component\Form\FormEvent;
// use Symfony\Component\Form\FormEvents;

class IndividuFormType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // dd($options);
        $noms = $options['srv_noms'];
        if (count($noms) > 0) {
            foreach ($noms as $n) {
                $builder->add(
                    'login_'.$n,
                    CheckboxType::class,
                    [
                        'label' => $n,
                        // 'mapped' => true,
                        'required' => false,
                        'attr' => ['title' => 'Demander l\'ouverture d\'un compte sur '.$n],
                    ]
                );
            }
        }
        $builder->setDataMapper($this);
        $builder->add(
            'mail',
            TextType::class,
            [
                'label' => 'email',
                'attr' => ['size' => '50'],
                'required' => false,
            ]
        );

        // NOTE - si text_fields vaut true, cela veut dire que les champs
        //        statut, laboratoire, etablissement sont disabled
        //        (cf. le paramètre resp_peut_modif_collabs)
        //
        $builder->add(
            'prenom',
            TextType::class,
            [
                'label' => 'prénom',
                'attr' => ['size' => '50'],
                'required' => false,
                'disabled' => false,
            ]
        )
        ->add(
            'nom',
            TextType::class,
            [
                'label' => 'nom',
                'attr' => ['size' => '50'],
                'required' => false,
                'disabled' => false,
            ]
        );

        if ($options['text_fields']) {
            $builder->add(
                'statut',
                TextType::class,
                [
                    'label' => 'statut',
                    'disabled' => true,
                    'required' => false,
                ]
            )
            ->add(
                'laboratoire',
                TextType::class,
                [
                    'label' => 'laboratoire',
                    'disabled' => true,
                    'required' => false,
                ]
            )
            ->add(
                'etablissement',
                TextType::class,
                [
                    'label' => 'établissement',
                    'disabled' => true,
                    'required' => false,
                ]
            )
            ;
        } else {
            $builder->add(
                'statut',
                EntityType::class,
                [
                    'label' => 'statut',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => false,
                    'class' => Statut::class,
                    'placeholder' => '-- Indiquez le statut',
                ]
            )
            ->add(
                'laboratoire',
                EntityType::class,
                [
                    'label' => 'laboratoire',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => false,
                    'class' => Laboratoire::class,
                    'placeholder' => '-- Indiquez le laboratoire',
                ]
            )
            ->add(
                'etablissement',
                EntityType::class,
                [
                    'label' => 'établissement',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => false,
                    'class' => Etablissement::class,
                    'placeholder' => "-- Indiquez l'établissement",
                ]
            );
        }
        $builder->add(
            'deleted',
            CheckboxType::class,
            [
                'label' => 'supprimer',
                'required' => false,
            ]
        );
        $builder->add(
            'id',
            HiddenType::class,
            [
            ]
        );

        //        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
        //            dd($event, $event->getForm(), $event->getForm()->children);
        //        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
            'data_class' => IndividuForm::class,
            'text_fields' => false,
            'srv_noms' => [],
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'Individu';
    }

    /**********
     * Fonctions de mapping
     * Voir ici https://symfony.com/doc/current/form/data_mappers.html
     ****************************/
    public function mapDataToForms($viewData, \Traversable $forms): void
    {
        // there is no data yet, so nothing to prepopulate
        if (null === $viewData) {
            return;
        }

        // invalid data type
        if (!$viewData instanceof IndividuForm) {
            throw new UnexpectedTypeException($viewData, IndividuForm::class);
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        // On initialise les champs login_XXX à partir du tableau logins de l'IndividuForm
        $vd_logins = $viewData->getLogins();
        foreach ($vd_logins as $srv => $login) {
            $field = 'login_'.$srv;
            // Sinon on ignore (ne devrait pas arriver !))
            if (isset($forms[$field])) {
                $forms[$field]->setData($login);
            }
        }

        // On initialise les autres champs comme avec le mapper par défaut
        // NOTE - Pour les champs marqués comme disabled cela ne servira à rien
        $forms['mail']->setData($viewData->getMail());
        $forms['prenom']->setData($viewData->getPrenom());
        $forms['nom']->setData($viewData->getNom());
        $forms['statut']->setData($viewData->getStatut());
        $forms['laboratoire']->setData($viewData->getLaboratoire());
        $forms['etablissement']->setData($viewData->getEtablissement());
        $forms['id']->setData($viewData->getId());
    }

    public function mapFormsToData(\Traversable $forms, &$viewData): void
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        // as data is passed by reference, overriding it will change it in
        // the form object as well
        // beware of type inconsistency, see caution below
        $vd_logins = [];
        foreach ($forms as $f => $field) {
            if (str_starts_with($f, 'login_')) {
                $k = str_replace('login_', '', $f);
                $vd_logins[$k] = empty($forms[$f]->getData()) ? false : true;
            }
        }
        $viewData->setLogins($vd_logins);

        // On initialise les autres champs comme avec le mapper par défaut

        $viewData->setMail($forms['mail']->getData());

        $viewData->setPrenom($forms['prenom']->getData());
        $viewData->setNom($forms['nom']->getData());
        $viewData->setStatut($forms['statut']->getData());
        $viewData->setLaboratoire($forms['laboratoire']->getData());
        $viewData->setEtablissement($forms['etablissement']->getData());
        $viewData->setId($forms['id']->getData());

        // dd($forms['mail']->getData(),$forms, $viewData, $vd_logins_src, $vd_logins_dst);
    }
}
