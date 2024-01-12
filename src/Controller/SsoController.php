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

use App\Entity\Sso;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Sso controller.
 *
 * @Security("is_granted('ROLE_ADMIN')")
 */
#[Route(path: 'sso')]
class SsoController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Lists all sso entities.
     */
    #[Route(path: '/', name: 'sso_index', methods: ['GET'])]
    public function indexAction(): Response
    {
        $em = $this->em;

        $ssos = $em->getRepository(Sso::class)->findAll();

        return $this->render('sso/index.html.twig', [
            'ssos' => $ssos,
        ]);
    }

    /**
     * Creates a new sso entity.
     */
    #[Route(path: '/new', name: 'sso_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request): Response
    {
        $sso = new Sso();
        $form = $this->createForm('App\Form\SsoType', $sso);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($sso);
            $em->flush($sso);

            return $this->redirectToRoute('sso_show', ['id' => $sso->getId()]);
        }

        return $this->render('sso/new.html.twig', [
            'sso' => $sso,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a sso entity.
     */
    #[Route(path: '/{id}', name: 'sso_show', methods: ['GET'])]
    public function showAction(Sso $sso): Response
    {
        $deleteForm = $this->createDeleteForm($sso);

        return $this->render('sso/show.html.twig', [
            'sso' => $sso,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing sso entity.
     */
    #[Route(path: '/{id}/edit', name: 'sso_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, Sso $sso): Response
    {
        $deleteForm = $this->createDeleteForm($sso);
        $editForm = $this->createForm('App\Form\SsoType', $sso);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->em->flush();

            return $this->redirectToRoute('sso_edit', ['id' => $sso->getId()]);
        }

        return $this->render('sso/edit.html.twig', [
            'sso' => $sso,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a sso entity.
     */
    #[Route(path: '/{id}', name: 'sso_delete', methods: ['DELETE'])]
    public function deleteAction(Request $request, Sso $sso): Response
    {
        $form = $this->createDeleteForm($sso);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->remove($sso);
            $em->flush($sso);
        }

        return $this->redirectToRoute('sso_index');
    }

    /**
     * Creates a form to delete a sso entity.
     *
     * @param Sso $sso The sso entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Sso $sso): Response
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('sso_delete', ['id' => $sso->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
