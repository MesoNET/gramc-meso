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

use App\Entity\Statut;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Statut controller.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route(path: 'statut')]
class StatutController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Lists all statut entities.
     */
    #[Route(path: '/', name: 'statut_index', methods: ['GET'])]
    public function indexAction(): Response
    {
        $em = $this->em;

        $statuts = $em->getRepository(Statut::class)->findAll();

        return $this->render('statut/index.html.twig', [
            'statuts' => $statuts,
        ]);
    }

    /**
     * Creates a new statut entity.
     */
    #[Route(path: '/new', name: 'statut_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request): Response
    {
        $statut = new Statut();
        $form = $this->createForm('App\Form\StatutType', $statut);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($statut);
            $em->flush($statut);

            return $this->redirectToRoute('statut_show', ['id' => $statut->getId()]);
        }

        return $this->render('statut/new.html.twig', [
            'statut' => $statut,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a statut entity.
     */
    #[Route(path: '/{id}', name: 'statut_show', methods: ['GET'])]
    public function showAction(Statut $statut): Response
    {
        $deleteForm = $this->createDeleteForm($statut);

        return $this->render('statut/show.html.twig', [
            'statut' => $statut,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing statut entity.
     */
    #[Route(path: '/{id}/edit', name: 'statut_edit', methods: ['GET'])]
    public function editAction(Request $request, Statut $statut): Response
    {
        $deleteForm = $this->createDeleteForm($statut);
        $editForm = $this->createForm('App\Form\StatutType', $statut);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->em->flush();

            return $this->redirectToRoute('statut_edit', ['id' => $statut->getId()]);
        }

        return $this->render('statut/edit.html.twig', [
            'statut' => $statut,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a statut entity.
     */
    #[Route(path: '/{id}', name: 'statut_delete', methods: ['DELETE'])]
    public function deleteAction(Request $request, Statut $statut): Response
    {
        $form = $this->createDeleteForm($statut);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->remove($statut);
            $em->flush($statut);
        }

        return $this->redirectToRoute('statut_index');
    }

    /**
     * Creates a form to delete a statut entity.
     *
     * @param Statut $statut The statut entity
     *
     * @return Form The form
     */
    private function createDeleteForm(Statut $statut): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('statut_delete', ['id' => $statut->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
