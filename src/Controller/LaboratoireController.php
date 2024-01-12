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

namespace App\Controller;

use App\Entity\Adresseip;
use App\Entity\Laboratoire;
use App\Utils\Functions;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Laboratoire controller.
 */
#[Route(path: 'laboratoire')]
class LaboratoireController extends AbstractController
{
    public function __construct(private AuthorizationCheckerInterface $ac, private EntityManagerInterface $em)
    {
    }

    /**
     * Liste tous les laboratoires.
     *
     */
    #[isGranted('ROLE_OBS')]
    #[Route(path: '/gerer', name: 'gerer_laboratoires', methods: ['GET'])]
    public function gererAction(): Response
    {
        $ac = $this->ac;
        $em = $this->em;

        // Si on n'est pas admin on n'a pas accès au menu
        $menu = ($ac->isGranted('ROLE_ADMIN') or $ac->isGranted('ROLE_VALIDEUR')) ? [['ok' => true, 'name' => 'ajouter_laboratoire', 'lien' => 'Ajouter un laboratoire', 'commentaire' => 'Ajouter un laboratoire']] : [];

        return $this->render(
            'laboratoire/liste.html.twig',
            [
            'menu' => $menu,
            'laboratoires' => $em->getRepository(Laboratoire::class)->findBy([], ['numeroLabo' => 'ASC']),
            ]
        );
    }

    /**
     * Ajoute un nouveau laboratoire.
     *
     */
    #[isGranted('ROLE_ADMIN'||'ROLE_VALIDEUR')]
    #[Route(path: '/ajouter', name: 'ajouter_laboratoire', methods: ['GET', 'POST'])]
    public function ajouterAction(Request $request): Response
    {
        $laboratoire = new Laboratoire();
        $form = $this->createForm('App\Form\LaboratoireType', $laboratoire, ['ajouter' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($laboratoire);
            if (Functions::Flush($em, $request)) {
                return $this->redirectToRoute('modifier_laboratoire', ['id' => $laboratoire->getId()]);
            }
        }

        return $this->render(
            'laboratoire/ajouter.html.twig',
            [
            'menu' => [[
                        'ok' => true,
                        'name' => 'gerer_laboratoires',
                        'lien' => 'Retour vers la liste des laboratoires',
                        'commentaire' => 'Retour vers la liste des laboratoires',
                        ]],
            'laboratoire' => $laboratoire,
            'form' => $form->createView(),
            ]
        );
    }

    /**
     * Modifie un laboratoire.
     *
     */
    #[isGranted('ROLE_ADMIN'||'ROLE_VALIDEUR')]
    #[Route(path: '/{id}/modifier', name: 'modifier_laboratoire', methods: ['GET', 'POST'])]
    public function modifierAction(Request $request, Laboratoire $laboratoire): Response
    {
        $em = $this->em;
        $editForm = $this->createForm('App\Form\LaboratoireType', $laboratoire, ['modifier' => true]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            if (Functions::Flush($em, $request)) {
                return $this->redirectToRoute('gerer_laboratoires');
            }
        }

        // Ajout d'une plage d'IP
        $formAdr = $this->ajoutAdresseIp($request, $laboratoire);
        if (null === $formAdr) {
            return $this->redirectToRoute('modifier_laboratoire', ['id' => $laboratoire->getId()]);
        }

        // Suppression d'une plage d'IP
        $formSadr = $this->supprAdressesIp($request, $laboratoire);
        if (null === $formSadr) {
            return $this->redirectToRoute('modifier_laboratoire', ['id' => $laboratoire->getId()]);
        }

        return $this->render(
            'laboratoire/modif.html.twig',
            [
            'menu' => [[
                        'ok' => true,
                        'name' => 'gerer_laboratoires',
                        'lien' => 'Retour vers la liste des laboratoires',
                        'commentaire' => 'Retour vers la liste des laboratoires',
                        ]],
            'laboratoire' => $laboratoire,
            'form' => $editForm->createView(),
            'formAdr' => $formAdr->createView(),
            'formSadr' => $formSadr->createView(),
            ]
        );
    }

    /*********************************
     *
     * Ajout d'une nouvelle plage ip - Si le formulaire est traité on renvoie null, ce qui
     * provoquera une redirection
     *
     **************************************/
    private function ajoutAdresseIp(Request $request, Laboratoire $laboratoire): ?FormInterface
    {
        $em = $this->em;
        $adresseip = new Adresseip();
        $adresseip->setLabo($laboratoire);
        $formAdr = $this->createForm('App\Form\AdresseipType', $adresseip, ['widget_laboratoire' => false]);
        $formAdr->handleRequest($request);

        if ($formAdr->isSubmitted() && $formAdr->isValid()) {
            $em->persist($adresseip);
            try {
                $em->flush($adresseip);
            } catch (\Exception $e) {
                $msg = 'Ajout impossible - Adresse dupliquée ?';
                $request->getSession()->getFlashbag()->add('flash erreur', $msg);
            }

            return null;
        }

        return $formAdr;
    }

    /*********************************
     *
     * Suppression d'une ou plusieurs plages IP - Si le formulaire est traité on renvoie null, ce qui
     * provoquera une redirection
     *
     **************************************/
    private function supprAdressesIp(Request $request, Laboratoire $laboratoire): ?FormInterface
    {
        $em = $this->em;

        $adresses = $laboratoire->getAdresseip();
        $adresses_old = clone $adresses;

        $formSadr = $this->createFormBuilder($laboratoire)
            ->add(
                'Adresseip',
                EntityType::class,
                [
                'label' => 'Les plages IP de ce laboratoire: ',
                'multiple' => true,
                'expanded' => true,
                'class' => Adresseip::class,
                'choices' => $laboratoire->getAdresseip(),
                'choice_label' => function ($s) { return $s->getAdresse(); },
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'Supprimer'])
            ->add('reset', ResetType::class, ['label' => 'Annuler'])
            ->getForm();

        $formSadr->handleRequest($request);

        if ($formSadr->isSubmitted() && $formSadr->isValid()) {
            foreach ($adresses_old as $adr) {
                if (!$adresses->contains($adr)) {
                    $em->remove($adr);
                }
            }
            $em->flush();

            return null;
        }

        return $formSadr;
    }

    /**
     * Supprime un laboratoire.
     *
     */
    #[isGranted('ROLE_ADMIN'||'ROLE_VALIDEUR')]
    #[Route(path: '/{id}/supprimer', name: 'supprimer_laboratoire', methods: ['GET'])]
    public function supprimerAction(Request $request, Laboratoire $laboratoire): Response
    {
        $em = $this->em;
        $em->remove($laboratoire);

        try {
            $em->flush();
        } catch (\Exception $e) {
            $msg = 'Suppression impossbile - Il reste probablement des individus appartenant à ce laboratoire';
            $request->getSession()->getFlashbag()->add('flash erreur', $msg);
        }

        return $this->redirectToRoute('gerer_laboratoires');
    }
}
