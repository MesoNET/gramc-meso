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

use App\Entity\CollaborateurVersion;
use App\Entity\Expertise;
use App\Entity\Individu;
use App\Entity\Invitation;
use App\Entity\Journal;
use App\Entity\Rallonge;
use App\Entity\Session;
use App\Entity\Sso;
use App\Entity\Thematique;
use App\Form\GererUtilisateurType;
use App\Form\IndividuForm\IndividuForm;
use App\GramcServices\ServiceExperts\ServiceExperts;
use App\GramcServices\ServiceIndividus;
use App\GramcServices\ServiceInvitations;
use App\GramcServices\ServiceJournal;
use App\Utils\Functions;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Individu controller.
 */
#[Route(path: 'individu')]
class IndividuController extends AbstractController
{
    public function __construct(
        private ServiceIndividus $sid,
        private ServiceExperts $se,
        private ServiceJournal $sj,
        private ServiceInvitations $si,
        private FormFactoryInterface $ff,
        private TokenStorageInterface $tok,
        private AuthorizationCheckerInterface $ac,
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Supprimer utilisateur.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/supprimer', name: 'supprimer_utilisateur', methods: ['GET'])]
    public function supprimerUtilisateurAction(Request $request, Individu $individu): Response
    {
        $em = $this->em;
        $em->remove($individu);
        $em->flush();

        return $this->redirectToRoute('individu_gerer');
    }

    /**
     * Remplacer utilisateur: on a demandé la suppression d'un utilisateur qui a des projets, expertises etc.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/remplacer', name: 'remplacer_utilisateur', methods: ['GET', 'POST'])]
    public function remplacerUtilisateurAction(Request $request, Individu $individu): Response
    {
        $em = $this->em;
        $sid = $this->sid;
        $sj = $this->sj;
        $ff = $this->ff;

        $session = $request->getSession();

        $form = $ff
            ->createNamedBuilder('autocomplete_form', FormType::class, [])
            ->add(
                'submit',
                SubmitType::class,
                [
                 'label' => 'Le nouvel utilisateur',
                 ]
            )
            ->getForm();

        // si on vient de modify, on préremplit le champ puis on retire new_mail de la session
        if ($session->has('new_mail')) {
            $form->add(
                'mail',
                TextType::class,
                [
                'required' => false, 'csrf_protection' => false, 'attr' => ['value' => $session->get('new_mail')],
                ]
            );
            $session->remove('new_mail');
        } else {
            $form->add(
                'mail',
                TextType::class,
                [
                'required' => false, 'csrf_protection' => false,
                ]
            );
        }

        $CollaborateurVersion = $em->getRepository(CollaborateurVersion::class)->findBy(['collaborateur' => $individu]);
        $Expertise = $em->getRepository(Expertise::class)->findBy(['expert' => $individu]);
        $Journal = $em->getRepository(Journal::class)->findBy(['individu' => $individu]);
        $Rallonge = $em->getRepository(Rallonge::class)->findBy(['expert' => $individu]);
        $Sso = $em->getRepository(Sso::class)->findBy(['individu' => $individu]);
        $Thematique = $individu->getThematique();
        $erreurs = [];

        // utilisateur peu actif peut être effacé
        if (null == $CollaborateurVersion && null == $Expertise && null == $Rallonge) {
            foreach ($individu->getThematique() as $item) {
                $em->persist($item);
                $item->getExpert()->removeElement($individu);
            }

            foreach ($Sso as $item) {
                $em->remove($item);
            }

            try {
                $em->remove($individu);
                $em->flush();
            } catch (\Exception $e) {
                $request->getSession()->getFlashbag()->add('flash erreur', $e->getMessage());
                $sj->warningMessage('Utilisateur '.$individu.' ('.$individu->getIdIndividu().') ne peut être effacé ');
            }

            $request->getSession()->getFlashbag()->add('flash info', $individu.' supprimé');
            $sj->infoMessage("Utilisateur $individu effacé");

            return $this->redirectToRoute('individu_gerer');
        }

        // utilisateur actif ou qui peut se connecter doit être remplacé
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mail = $form->getData()['mail'];
            $new_individu = $em->getRepository(Individu::class)->findOneBy(['mail' => $mail]);

            if (null != $new_individu) {
                try {
                    $sid->fusionnerIndividus($individu, $new_individu);
                    $em->remove($individu);
                    $em->flush();
                } catch (\Exception $e) {
                    $request->getSession()->getFlashbag()->add('flash erreur', $e->getMessage());
                    $sj->warningMessage('Utilisateur '.$individu.' ('.$individu->getIdIndividu().') ne peut être effacé ');
                }

                $request->getSession()->getFlashbag()->add('flash info', $individu.' supprimé');
                $sj->infoMessage('Utilisateur '.$individu.'('.$individu->getIdIndividu()
                .') fusionné vers '.$new_individu.' ('.$new_individu->getIdIndividu().')');

                return $this->redirectToRoute('individu_gerer');
            } else {
                $erreurs[] = 'Le mail du nouvel utilisateur "'.$mail.'" ne correspond à aucun utilisateur existant';
            }
        }

        return $this->render(
            'individu/remplacer.html.twig',
            [
                'form' => $form->createView(),
                'erreurs' => $erreurs,
                'CollaborateurVersion' => $CollaborateurVersion,
                'Expertise' => $Expertise,
                'Journal ' => $Journal,
                'Rallonge' => $Rallonge,
                'Sso' => $Sso,
                'individu' => $individu,
                'Thematique' => $Thematique->toArray(),
            ]
        );
    }

    /**
     * Deletes a individu entity (CRUD).
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/delete', name: 'individu_delete', methods: ['DELETE'])]
    public function deleteAction(Request $request, Individu $individu): Response
    {
        $form = $this->createDeleteForm($individu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->remove($individu);
            $em->flush($individu);
        }

        return $this->redirectToRoute('individu_index');
    }

    /**
     * Lists all individu entities (CRUD).
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/', name: 'individu_index', methods: ['GET'])]
    public function indexAction(): Response
    {
        $em = $this->em;

        $individus = $em->getRepository(Individu::class)->findAll();

        return $this->render('individu/index.html.twig', [
            'individus' => $individus,
        ]);
    }

    /**
     * Creates a new individu entity (CRUD).
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/new', name: 'individu_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request): Response
    {
        $individu = new Individu();
        $form = $this->createForm('App\Form\IndividuType', $individu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($individu);
            $em->flush($individu);

            return $this->redirectToRoute('individu_show', ['id' => $individu->getId()]);
        }

        return $this->render('individu/new.html.twig', [
            'individu' => $individu,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a individu entity (CRUD).
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/show', name: 'individu_show', methods: ['GET'])]
    public function showAction(Individu $individu): Response
    {
        $deleteForm = $this->createDeleteForm($individu);

        return $this->render('individu/show.html.twig', [
            'individu' => $individu,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing individu entity (CRUD).
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/edit', name: 'individu_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, Individu $individu): Response
    {
        $deleteForm = $this->createDeleteForm($individu);
        $editForm = $this->createForm('App\Form\IndividuType', $individu);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->em->flush();

            return $this->redirectToRoute('individu_edit', ['id' => $individu->getId()]);
        }

        return $this->render('individu/edit.html.twig', [
            'individu' => $individu,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to delete a individu entity.
     *
     * @param Individu $individu The individu entity
     *
     * @return FormInterface The form
     */
    private function createDeleteForm(Individu $individu): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('individu_delete', ['id' => $individu->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Ajouter un individu.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/ajouter', name: 'individu_ajouter', methods: ['GET', 'POST'])]
    public function ajouterAction(Request $request)
    {
        $em = $this->em;
        $individu = new Individu();
        $editForm = $this->createForm('App\Form\IndividuType', $individu);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() /* && $editForm->isValid() */) {
            $individu->setCreationStamp(new \DateTime());
            $em->persist($individu);
            $em->flush();

            return $this->redirectToRoute('individu_gerer');
        }

        return $this->render(
            'individu/modif.html.twig',
            [
            'individu' => $individu,
            'formInd' => $editForm->createView(),
            'formSso' => null,
            'formEppn' => null,
            ]
        );
    }

    /**
     * Modifier un individu.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/modify', name: 'individu_modify', methods: ['GET', 'POST'])]
    public function modifyAction(Request $request, Individu $individu): Response
    {
        $em = $this->em;
        $repos = $em->getRepository(Individu::class);

        $formInd = $this->createForm('App\Form\IndividuType', $individu);
        $session = $request->getSession();

        $formInd->handleRequest($request);
        if ($formInd->isSubmitted() /* && $editForm->isValid() */) {
            $exc = false;
            try {
                $em->flush();
            } catch (UniqueConstraintViolationException $e) {
                $exc = true;
            }

            // Si exception, aller vers l'écran de remplacement
            if ($exc) {
                $session->set('new_mail', $individu->getMail());

                return $this->redirectToRoute('remplacer_utilisateur', ['id' => $individu->getIdIndividu()]);
            } else {
                return $this->redirectToRoute('individu_gerer');
            }
        }

        // Nouvel EPPN
        $formSso = $this->ajoutEppn($request, $individu);
        if (null == $formSso) {
            return $this->redirectToRoute('individu_modify', ['id' => $individu->getId()]);
        }

        // Supprimer un EPPN
        $ssos = $individu->getSso();
        $ssos_old = clone $ssos;
        $formEppn = $this->createFormBuilder($individu)
            ->add(
                'Sso',
                EntityType::class,
                [
                'label' => 'Les eppn de cet individu: ',
                'multiple' => true,
                'expanded' => true,
                'class' => Sso::class,
                'choices' => $individu->getSso(),
                'choice_label' => function ($s) { return $s->getEppn(); },
                'choice_value' => function ($t) { return $t; },
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'modifier'])
            ->add('reset', ResetType::class, ['label' => 'Annuler'])
            ->getForm();

        $formEppn->handleRequest($request);

        if ($formEppn->isSubmitted() && $formEppn->isValid()) {
            foreach ($ssos_old as $s) {
                if (!$ssos->contains($s)) {
                    $em->remove($s);
                }
            }
            $em->flush();

            return $this->redirectToRoute('individu_modify', ['id' => $individu->getId()]);
        }

        return $this->render(
            'individu/modif.html.twig',
            [
            'individu' => $individu,
            'formInd' => $formInd->createView(),
            'formEppn' => $formEppn->createView(),
            'formSso' => $formSso->createView(),
            ]
        );
    }

    /**
     * Envoyer une invitation.
     *
     **/
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/invitation', name: 'invitation', methods: ['GET'])]
    public function invitationAction(Request $request, Individu $individu): Response
    {
        $token = $this->tok->getToken();
        $user = $token->getUser();
        $this->si->sendInvitation($user, $individu);
        $request->getSession()->getFlashbag()->add('flash info', "Invitation envoyée à $individu");

        return $this->redirectToRoute('individu_gerer');
    }

    /**
     * Afficher toutes les invitations.
     *
     **/
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/invitations', name: 'invitations', methods: ['GET'])]
    public function invitationsAction(Request $request): Response
    {
        $em = $this->em;

        $invitations = $em->getRepository(Invitation::class)->findAll();
        $invit_duree = $this->getParameter('invit_duree');
        $duree = new \DateInterval($invit_duree);

        return $this->render('individu/invitations.html.twig', ['invitations' => $invitations, 'duree' => $duree]);
    }

    /**
     * Supprimer une invitation.
     *
     **/
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/supprimer_invitation', name: 'supprimer_invitation', methods: ['GET'])]
    public function supprimerInvitationAction(Request $request, Invitation $invitation): Response
    {
        $em = $this->em;

        $em->remove($invitation);
        $em->flush();

        return $this->redirectToRoute('invitations');
    }

    /*********************************
     *
     * Ajout d'un nouvel eppn
     *
     **************************************/
    private function ajoutEppn(Request $request, Individu $individu): ?FormInterface
    {
        $em = $this->em;
        $sso = new Sso();
        $sso->setIndividu($individu);
        $formSso = $this->createForm('App\Form\SsoType', $sso, ['widget_individu' => false]);
        $formSso
            ->add('submit', SubmitType::class, ['label' => 'nouvel EPPN'])
            ->add('reset', ResetType::class, ['label' => 'Annuler']);

        $formSso->handleRequest($request);

        if ($formSso->isSubmitted() && $formSso->isValid()) {
            $em->persist($sso);

            try {
                $em->flush($sso);
            } catch (UniqueConstraintViolationException $e) {
                $request->getSession()->getFlashbag()->add('flash erreur', 'Cet eppn existe déjà !');

                return null;
            }
        }

        return $formSso;
    }

    /**
     * Devenir Admin.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/devenir_admin', name: 'devenir_admin', methods: ['GET'])]
    public function devenirAdminAction(Request $request, Individu $individu): Response
    {
        $individu->setAdmin(true);
        $individu->setObs(false);    // Pas la peine d'être Observateur si on est admin !

        $em = $this->em;
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest()) {
            return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
        } else {
            return $this->redirectToRoute('individu_gerer');
        }
    }

    /**
     * Cesser d'être Admin.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/plus_admin', name: 'plus_admin', methods: ['GET'])]
    public function plusAdminAction(Request $request, Individu $individu): Response
    {
        $individu->setAdmin(false);
        $em = $this->em;
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest()) {
            return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
        } else {
            return $this->redirectToRoute('individu_gerer');
        }
    }

    /**
     * Devenir Obs.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/devenir_obs', name: 'devenir_obs', methods: ['GET'])]
    public function devenirObsAction(Request $request, Individu $individu): Response
    {
        $individu->setObs(true);
        $individu->setAdmin(false); // Si on devient Observateur on n'est plus admin !
        $em = $this->em;
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest()) {
            return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
        } else {
            return $this->redirectToRoute('individu_gerer');
        }
    }

    /**
     * Cesser d'être Obs.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/plus_obs', name: 'plus_obs', methods: ['GET'])]
    public function plusObsAction(Request $request, Individu $individu): Response
    {
        $individu->setObs(false);
        $em = $this->em;
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest()) {
            return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
        } else {
            return $this->redirectToRoute('individu_gerer');
        }
    }

    /**
     * Devenir Sysadmin.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/devenir_sysadmin', name: 'devenir_sysadmin', methods: ['GET'])]
    public function devenirSysadminAction(Request $request, Individu $individu): Response
    {
        $individu->setSysadmin(true);
        $em = $this->em;
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest()) {
            return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
        } else {
            return $this->redirectToRoute('individu_gerer');
        }
    }

    /**
     * Cesser d'être Sysadmin.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/plus_sysadmin', name: 'plus_sysadmin', methods: ['GET'])]
    public function plusSysadminAction(Request $request, Individu $individu): Response
    {
        $individu->setSysadmin(false);
        $em = $this->em;
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest()) {
            return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
        } else {
            return $this->redirectToRoute('individu_gerer');
        }
    }

    /**
     * Devenir President - PAS UTILISE.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/devenir_president', name: 'devenir_president', methods: ['GET'])]
    public function devenirPresidentAction(Request $request, Individu $individu): Response
    {
        $individu->setPresident(true);
        $em = $this->em;
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest()) {
            return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
        } else {
            return $this->redirectToRoute('individu_gerer');
        }
    }

    /**
     * Cesser d'être President - PAS UTILISE.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/plus_president', name: 'plus_president', methods: ['GET'])]
    public function plusPresidentAction(Request $request, Individu $individu): Response
    {
        $individu->setPresident(false);
        $em = $this->em;
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest()) {
            return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
        } else {
            return $this->redirectToRoute('individu_gerer');
        }
    }

    /**
     * Devenir Expert - PAS UTILISE.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/devenir_expert', name: 'devenir_expert', methods: ['GET'])]
    public function devenirExpertAction(Request $request, Individu $individu): Response
    {
        $individu->setExpert(true);
        $em = $this->em;
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest()) {
            return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
        } else {
            return $this->redirectToRoute('individu_gerer');
        }
    }

    /**
     * Cesser d'être Expert - PAS UTILISE.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/plus_expert', name: 'plus_expert', methods: ['GET'])]
    public function plusExpertAction(Request $request, Individu $individu): Response
    {
        $em = $this->em;
        $se = $this->se;

        $individu->setExpert(false);
        $em->persist($individu);

        // TODO - Appeler ICI noThematique les autres appels sont sans doute inutiles !
        $se->noThematique($individu);
        $em->flush();

        return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
    }

    /**
     * Devenir valideur.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/devenir_valideur', name: 'devenir_valideur', methods: ['GET'])]
    public function devenirValideurAction(Request $request, Individu $individu): Response
    {
        $individu->setValideur(true);
        $em = $this->em;
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest()) {
            return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
        } else {
            return $this->redirectToRoute('individu_gerer');
        }
    }

    /**
     * Cesser d'être valideur.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/plus_valideur', name: 'plus_valideur', methods: ['GET'])]
    public function plusValideurAction(Request $request, Individu $individu): Response
    {
        $em = $this->em;
        $se = $this->se;

        $individu->setValideur(false);
        $em->persist($individu);

        $em->flush();

        return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
    }

    /**
     * Activer individu.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/activer', name: 'activer_utilisateur', methods: ['GET'])]
    public function activerAction(Request $request, Individu $individu)
    {
        $individu->setDesactive(false);
        $em = $this->em;
        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest()) {
            return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
        } else {
            return $this->redirectToRoute('individu_gerer');
        }
    }

    /**
     * Desactiver individu.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/desactiver', name: 'desactiver_utilisateur', methods: ['GET'])]
    public function desactiverAction(Request $request, Individu $individu): Response
    {
        $em = $this->em;

        $individu->setDesactive(true);

        // $ssos = $individu->getSso();
        // foreach ($ssos as $sso) {
        //    $em->remove($sso);
        // }

        $em->persist($individu);
        $em->flush($individu);

        if ($request->isXmlHttpRequest()) {
            return $this->render('individu/ligne.html.twig', ['individu' => $individu]);
        } else {
            return $this->redirectToRoute('individu_gerer');
        }
    }

    /**
     * Affecter l'individu à une ou des thematiques - PAS UTILISE.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/thematique', name: 'choisir_thematique', methods: ['GET', 'POST'])]
    public function thematiqueAction(Request $request, Individu $individu): Response
    {
        $em = $this->em;
        $form = $this->createFormBuilder($individu)
            ->add(
                'thematique',
                EntityType::class,
                [
                'multiple' => true,
                'expanded' => true,
                'class' => Thematique::class,
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'modifier'])
            ->add('reset', ResetType::class, ['label' => 'reset'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // thématiques && Doctrine ManyToMany
            $all_thematiques = $em->getRepository(Thematique::class)->findAll();
            $my_thematiques = $individu->getThematique();

            foreach ($all_thematiques as $thematique) {
                if ($my_thematiques->contains($thematique)) {
                    $thematique->addExpert($individu);
                } else {
                    $thematique->removeExpert($individu);
                }
            }
            $em->flush();
        }

        return $this->render(
            'individu/thematique.html.twig',
            [
            'individu' => $individu,
            'form' => $form->createView(),
        ]
        );
    }

    /**
     * Supprimer un ou plusieurs eppn de cet utilisateur.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/eppn', name: 'gere_eppn', methods: ['GET', 'POST'])]
    public function eppnAction(Request $request, Individu $individu): Response
    {
        $em = $this->em;
        $ssos = $individu->getSso();
        $form = $this->createFormBuilder($individu)
            ->add(
                'eppn',
                EntityType::class,
                [
                'multiple' => true,
                'expanded' => true,
                'class' => Sso::class,
                'choices' => $ssos,
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'modifier'])
            ->add('reset', ResetType::class, ['label' => 'reset'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // thématiques && Doctrine ManyToMany
            $all_thematiques = $em->getRepository(Thematique::class)->findAll();
            $my_thematiques = $individu->getThematique();

            foreach ($all_thematiques as $thematique) {
                if ($my_thematiques->contains($thematique)) {
                    $thematique->addExpert($individu);
                } else {
                    $thematique->removeExpert($individu);
                }
            }

            $em->flush();
        }

        return $this->render(
            'individu/thematique.html.twig',
            [
            'individu' => $individu,
            'form' => $form->createView(),
            ]
        );
    }

    /**
     * Autocomplete: en lien avec l'autocomplete de jquery
     *               Requête appelée lorsqu'on quitte le champ autocomplete "mail" dans le formulaire des collaborateurs.
     *
     */
    #[isGranted('ROLE_DEMANDEUR')]
    #[Route(path: '/mail_autocomplete', name: 'mail_autocomplete', methods: ['GET', 'POST'])]
    public function mailAutocompleteAction(Request $request): Response
    {
        $sj = $this->sj;
        $ff = $this->ff;
        $em = $this->em;
        $form = $ff
            ->createNamedBuilder('autocomplete_form', FormType::class, [])
            ->add('mail', TextType::class, ['required' => true, 'csrf_protection' => false])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) { // TODO - nous ne pouvons pas ajouter $form->isValid() et nous ne savons pas pourquoi
            if (array_key_exists('mail', $form->getData())) {
                $data = $em->getRepository(Individu::class)->liste_mail_like($form->getData()['mail']);
            } else {
                $data = [['mail' => 'Problème avec AJAX dans IndividuController:mailAutocompleteAction']];
            }

            $output = [];
            foreach ($data as $item) {
                if (array_key_exists('mail', $item)) {
                    $output[] = $item['mail'];
                }
            }

            $response = new Response(json_encode($output));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        // on complète le reste des informations
        // TODO - IndividuForm n'est PAS un objet de type Form !!!! Grrrrr
        //        $form est un objet de type IndividuFormType, c'est bien un form associé à un object de type IndividuForm
        $collaborateur = new IndividuForm();
        $text_fields = true;
        if ($this->getParameter('resp_peut_modif_collabs')) {
            $text_fields = false;
        }
        $form = $this->createForm('App\Form\IndividuFormType', $collaborateur, ['csrf_protection' => false, 'text_fields' => $text_fields]);

        $form->handleRequest($request);

        // On vient de soumettre le formulaire via son adresse mail
        if ($form->isSubmitted() && $form->isValid()) {
            // On recherche l'individu ayant le bon mail et on complète l'objet $collaborateur
            $individu = $em->getRepository(Individu::class)->findOneBy(['mail' => $collaborateur->getMail()]);
            if (null != $individu) {
                if (null != $individu->getMail()) {
                    $collaborateur->setMail($individu->getMail());
                }
                if (null != $individu->getPrenom()) {
                    $collaborateur->setPrenom($individu->getPrenom());
                }
                if (null != $individu->getNom()) {
                    $collaborateur->setNom($individu->getNom());
                }
                if (null != $individu->getStatut()) {
                    $collaborateur->setStatut($individu->getStatut());
                }
                if (null != $individu->getLabo()) {
                    $collaborateur->setLaboratoire($individu->getLabo());
                }
                if (null != $individu->getEtab()) {
                    $collaborateur->setEtablissement($individu->getEtab());
                }
                if (null != $individu->getId()) {
                    $collaborateur->setId($individu->getId());
                }

                // Maintenant on recrée un $form en utilisant le $collaborateur complété
                $text_fields = true;
                if ($this->getParameter('resp_peut_modif_collabs')) {
                    $text_fields = false;
                }
                $form = $this->createForm('App\Form\IndividuFormType', $collaborateur, ['csrf_protection' => false, 'text_fields' => $text_fields]);

                return $this->render('version/collaborateurs_ligne.html.twig', ['form' => $form->createView()]);
            } else {
                return new Response('reallynouserrrrrrrr');
            }
        }

        // return new Response( 'no form submitted' );
        return new Response(json_encode('no form submitted'));
    }

    /**
     * Liste tous les individus.
     *
     */
    #[isGranted('ROLE_ADMIN')]
    #[Route(path: '/gerer', name: 'individu_gerer', methods: ['GET', 'POST'])]
    #[Route(path: '/liste', methods: ['GET', 'POST'])]
    public function gererAction(Request $request): Response
    {
        $ff = $this->ff;
        $em = $this->em;

        $form = $ff->createNamedBuilder('tri', GererUtilisateurType::class, [], [])->getform();
        // $form = Functions::getFormBuilder($ff, 'tri', GererUtilisateurType::class, [])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (true == $form->getData()['all']) {
                $users = $em->getRepository(Individu::class)->findAll();
            } else {
                $users = $em->getRepository(Individu::class)->getActiveUsers();
            }

            $pattern = '/'.$form->getData()['filtre'].'/i';

            $individus = [];
            foreach ($users as $individu) {
                if (preg_match($pattern, $individu->getMail())) {
                    $individus[] = $individu;
                } elseif (preg_match($pattern, $individu->getNom())) {
                    $individus[] = $individu;
                } elseif (preg_match($pattern, $individu->getPrenom())) {
                    $individus[] = $individu;
                }
            }
        } else {
            $individus = $em->getRepository(Individu::class)->getActiveUsers();
        }

        // statistiques
        $total = $em->getRepository(Individu::class)->countAll();
        $actifs = 0;
        $idps = [];
        foreach ($individus as $individu) {
            $individu_ssos = $individu->getSso()->toArray();
            if (count($individu_ssos) > 0 && false == $individu->getDesactive()) {
                ++$actifs;
            }

            $idps = array_merge(
                $idps,
                array_map(
                    function ($value) {
                        $str = $value->__toString();
                        preg_match('/^(.+)(@.+)$/', $str, $matches);
                        if (array_key_exists(2, $matches)) {
                            return $matches[2];
                        } else {
                            return '@';
                        }
                    },
                    $individu_ssos
                )
            );
        }
        $idps = array_count_values($idps);

        return $this->render(
            'individu/liste.html.twig',
            [
            'idps' => $idps,
            'total' => $total,
            'actifs' => $actifs,
            'form' => $form->createView(),
            'individus' => $individus,
            ]
        );
    }

    private static function sso_to_string($sso, $key): string
    {
        return $sso->__toString();
    }
}
