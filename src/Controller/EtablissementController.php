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

use App\Entity\Etablissement;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Etablissement controller.
 *
 * @Security("is_granted('ROLE_ADMIN')")
 */
#[Route(path: 'etablissement')]
class EtablissementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Lists all etablissement entities.
     */
    #[Route(path: '/', name: 'etablissement_index', methods: ['GET'])]
    public function indexAction(): Response
    {
        $em = $this->em;

        $etablissements = $em->getRepository(Etablissement::class)->findAll();

        return $this->render('etablissement/index.html.twig', [
            'etablissements' => $etablissements,
        ]);
    }

    /**
     * Creates a new etablissement entity.
     */
    #[Route(path: '/new', name: 'etablissement_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request): Response
    {
        $etablissement = new Etablissement();
        $form = $this->createForm('App\Form\EtablissementType', $etablissement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($etablissement);
            $em->flush($etablissement);

            return $this->redirectToRoute('etablissement_show', ['id' => $etablissement->getId()]);
        }

        return $this->render('etablissement/new.html.twig', [
            'etablissement' => $etablissement,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a etablissement entity.
     */
    #[Route(path: '/{id}', name: 'etablissement_show', methods: ['GET'])]
    public function showAction(Etablissement $etablissement): Response
    {
        $deleteForm = $this->createDeleteForm($etablissement);

        return $this->render('etablissement/show.html.twig', [
            'etablissement' => $etablissement,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing etablissement entity.
     */
    #[Route(path: '/{id}/edit', name: 'etablissement_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, Etablissement $etablissement): Response
    {
        $deleteForm = $this->createDeleteForm($etablissement);
        $editForm = $this->createForm('App\Form\EtablissementType', $etablissement);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->em->flush();

            return $this->redirectToRoute('etablissement_edit', ['id' => $etablissement->getId()]);
        }

        return $this->render('etablissement/edit.html.twig', [
            'etablissement' => $etablissement,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a etablissement entity.
     */
    #[Route(path: '/{id}', name: 'etablissement_delete', methods: ['DELETE'])]
    public function deleteAction(Request $request, Etablissement $etablissement): Response
    {
        $form = $this->createDeleteForm($etablissement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->remove($etablissement);
            $em->flush($etablissement);
        }

        return $this->redirectToRoute('etablissement_index');
    }

    /**
     * Creates a form to delete a etablissement entity.
     *
     * @param Etablissement $etablissement The etablissement entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Etablissement $etablissement): Response
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('etablissement_delete', ['id' => $etablissement->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
