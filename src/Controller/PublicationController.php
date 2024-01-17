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

use App\Entity\Projet;
use App\Entity\Publication;
use App\Form\PublicationType;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceSessions;
use App\Utils\Functions;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Publication controller.
 */
#[Route(path: 'publication')]
class PublicationController extends AbstractController
{
    public function __construct(
        private ServiceJournal $sj,
        private ServiceSessions $ss,
        private FormFactoryInterface $ff,
        private TokenStorageInterface $tok,
        private AuthorizationCheckerInterface $ac,
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Autocomplete publication.
     */
    #[isGranted('ROLE_DEMANDEUR')]
    #[Route(path: '/autocomplete', name: 'publication_autocomplete', methods: ['GET', 'POST'])]
    public function autocompleteAction(Request $request): Response
    {
        $sj = $this->sj;
        $em = $this->em;

        $sj->debugMessage('autocompleteAction '.print_r($_POST, true));
        $form = $this->ff
                    ->createNamedBuilder('autocomplete_form', FormType::class, [])
                    ->add('refbib', TextType::class, ['required' => true, 'csrf_protection' => false])
                    ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) { // nous ne pouvons pas ajouter $form->isValid() et nous ne savons pas pourquoi
            if (array_key_exists('refbib', $form->getData())) {
                $data = $em->getRepository(Publication::class)->liste_refbib_like($form->getData()['refbib']);
            } else {
                $data = [['refbib' => 'Problème avec AJAX dans PublicationController:autocompleteAction']];
            }

            $output = [];
            foreach ($data as $item) {
                if (array_key_exists('refbib', $item)) {
                    $output[] = $item['refbib'];
                }
            }

            $response = new Response(json_encode($output));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        // on complète le reste des informations

        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication, ['csrf_protection' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publication = $em->getRepository(Publication::class)->findOneBy(['refbib' => $publication->getRefbib()]);

            if (null != $publication) {
                return new Response(json_encode($publication));
            // $form = $this->createForm(PublicationType::class, $publication, ['csrf_protection' => true]);
            // return $this->render('publication/form.html.twig', [ 'form' => $form->createView() ]);
            } else {
                return new Response('nopubli');
            }
        }
        $form = $this->createForm(PublicationType::class, $publication, ['csrf_protection' => true]);

        return new Response(json_encode('no form submitted'));
    }

    /**
     * Lists all publication entities.
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/', name: 'publication_index', methods: ['GET'])]
    public function indexAction(): Response
    {
        $em = $this->em;

        $publications = $em->getRepository(Publication::class)->findAll();

        return $this->render('publication/index.html.twig', [
            'publications' => $publications,
        ]);
    }

    #[isGranted('ROLE_DEMANDEUR')]
    #[Route(path: '/{id}/gerer', name: 'gerer_publications', methods: ['GET', 'POST'])]
    public function gererAction(Projet $projet, Request $request, LoggerInterface $lg): Response
    {
        $sj = $this->sj;
        $em = $this->em;

        $publication = new Publication();
        $form = $this->createForm('App\Form\PublicationType', $publication);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null != $publication->getIdPubli()) {
                $sj->noticeMessage('PublicationController gererAction : La publication '.$publication->getIdPubli().' est partagée par plusieurs projets');
                $old = $em->getRepository(Publication::class)->find($publication->getIdPubli());
                if ($old->getRefbib() != $publication->getRefbib()) {
                    $sj->warningMessage('Changement de REFBIB de la publication '.$publication->getIdPubli());
                    $old->setRefbib($publication->getRefbib());
                }

                if ($old->getDoi() != $publication->getDoi()) {
                    $sj->warningMessage('Changement de DOI de la publication '.$publication->getIdPubli());
                    $old->setDoi($publication->getDoi());
                }

                if ($old->getOpenUrl() != $publication->getOpenUrl()) {
                    $sj->warningMessage('Changement de OpenUrl de la publication '.$publication->getIdPubli());
                    $old->setOpenUrl($publication->getOpenUrl());
                }

                if ($old->getAnnee() != $publication->getAnnee()) {
                    $sj->warningMessage("Changement d'année de la publication ".$publication->getIdPubli());
                    $old->setAnnee($publication->getAnnee());
                }

                $publication = $old;
            }

            $projet->addPubli($publication);
            $publication->addProjet($projet);
            Functions::sauvegarder($publication, $em, $lg);
            Functions::sauvegarder($projet, $em, $lg);
        }

        $form = $this->createForm('App\Form\PublicationType', new Publication()); // on efface le formulaire

        return $this->render(
            'publication/liste.html.twig',
            [
            'publications' => $projet->getPubli(),
            'form' => $form->createView(),
            'projet' => $projet,
        ]
        );
    }

    #[isGranted('ROLE_VALIDEUR')]
    #[Route(path: '/{id}/consulter', name: 'consulter_publications', methods: ['GET'])]
    public function consulterAction(Projet $projet, Request $request): Response
    {
        return $this->render(
            'publication/consulter.html.twig',
            [
            'publications' => $projet->getPubli(),
            'projet' => $projet,
            ]
        );
    }

    #[IsGranted(new Expression('is_granted("ROLE_OBS") or is_granted("ROLE_PRESIDENT")'))]
    #[Route(path: '/annee', name: 'publication_annee', methods: ['GET', 'POST'])]
    public function AnneeAction(Request $request): Response
    {
        $ss = $this->ss;
        $data = $ss->selectAnnee($request); // formulaire
        $annee = $data['annee'];
        $em = $this->em;
        $publications = $em->getRepository(Publication::class)->findBy(['annee' => $annee]);

        return $this->render(
            'publication/annee.html.twig',
            [
            'form' => $data['form']->createView(), // formulaire
            'annee' => $annee,
            'publications' => $publications,
            ]
        );
    }

    #[IsGranted(new Expression('is_granted("ROLE_OBS") or is_granted("ROLE_PRESIDENT")'))]
    #[Route(path: '/{annee}/annee_csv', name: 'publication_annee_csv', methods: ['GET', 'POST'])]
    public function AnneeCsvAction($annee): Response
    {
        $em = $this->em;
        $publications = $em->getRepository(Publication::class)->findBy(['annee' => $annee]);

        $header = [
                    'Référence',
                    'annee',
                    'doi',
                    'URL',
                    'Projets',
                    ];

        $sortie = join("\t", $header)."\n";
        foreach ($publications as $publi) {
            $line = [];
            $line[] = '"'.str_replace(["\n", "\r\n", "\t", '"'], [' ', ' ', ' ', ' '], $publi->getRefbib()).'"';
            // $line[] = 'ref';
            $line[] = $publi->getAnnee();
            $line[] = $publi->getDoi();
            $line[] = $publi->getOpenUrl();
            $line[] = join(' ', $publi->getProjet()->toArray());
            $sortie .= join("\t", $line)."\n";
        }

        return Functions::csv($sortie, 'publis_'.$annee.'.csv');
    }

    /**
     * Creates a new publication entity.
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/new', name: 'publication_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request): Response
    {
        $publication = new Publication();
        $form = $this->createForm('App\Form\PublicationType', $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($publication);
            $em->flush();

            return $this->redirectToRoute('publication_show', ['id' => $publication->getId()]);
        }

        return $this->render('publication/new.html.twig', [
            'publication' => $publication,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a publication entity.
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/show', name: 'publication_show', methods: ['GET'])]
    public function showAction(Publication $publication): Response
    {
        $deleteForm = $this->createDeleteForm($publication);

        return $this->render('publication/show.html.twig', [
            'publication' => $publication,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing publication entity.
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/edit', name: 'publication_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, Publication $publication): Response
    {
        $deleteForm = $this->createDeleteForm($publication);
        $editForm = $this->createForm('App\Form\PublicationType', $publication);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->em->flush();

            return $this->redirectToRoute('publication_edit', ['id' => $publication->getId()]);
        }

        return $this->render('publication/edit.html.twig', [
            'publication' => $publication,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing publication entity.
     */
    #[isGranted('ROLE_DEMANDEUR')]
    #[Route(path: '/{id}/{projet}/modify', name: 'modifier_publication', methods: ['GET', 'POST'])]
    public function modifyAction(Request $request, Publication $publication, Projet $projet, LoggerInterface $lg): Response
    {
        $sj = $this->sj;
        $em = $this->em;

        $editForm = $this->createForm('App\Form\PublicationType', $publication);
        $editForm->handleRequest($request);

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('supprimer_publication', ['id' => $publication->getId(), 'projet' => $projet->getIdProjet()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            Functions::sauvegarder($publication, $em, $lg);
            if (count($publication->getProjet()) > 1) {
                $sj->warningMessage('Modification de la publication  '.$publication->getIdPubli().' partagée par plusieurs projets');
            }

            return $this->redirectToRoute('gerer_publications', ['id' => $projet->getIdProjet()]);
        }

        return $this->render('publication/modify.html.twig', [
            'publication' => $publication,
            'projet' => $projet,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a publication entity.
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}', name: 'publication_delete', methods: ['DELETE'])]
    public function deleteAction(Request $request, Publication $publication): Response
    {
        $form = $this->createDeleteForm($publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->remove($publication);
            $em->flush();
        }

        return $this->redirectToRoute('publication_index');
    }

    /**
     * Deletes a publication entity.
     */
    #[isGranted('ROLE_DEMANDEUR')]
    #[Route(path: '/{id}/{projet}/supprimer', name: 'supprimer_publication', methods: ['GET', 'DELETE'])]
    public function supprimerAction(Request $request, Publication $publication, Projet $projet, LoggerInterface $lg): Response
    {
        $ac = $this->ac;
        $token = $this->tok->getToken();
        $sj = $this->sj;
        $em = $this->em;

        // ACL
        if (!$projet->isCollaborateur($token->getUser()) && !$ac->isGranted('ROLE_ADMIN')) {
            $sj->throwException();
        }

        $projet->removePubli($publication);
        $publication->removeProjet($projet);
        Functions::sauvegarder($projet, $em, $lg);
        Functions::sauvegarder($publication, $em, $lg);

        if (null == $publication->getProjet()) {
            $em = $this->em;
            $em->remove($publication);
            $em->flush();
        }

        return $this->redirectToRoute('gerer_publications', ['id' => $projet->getIdProjet()]);
    }

    /**
     * Creates a form to delete a publication entity.
     *
     * @param Publication $publication The publication entity
     *
     * @return Response The form
     */
    #[isGranted('ROLE_ADMIN')]
    private function createDeleteForm(Publication $publication): Response
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('publication_delete', ['id' => $publication->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
