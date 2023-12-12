<?php

/**
 * This file is part of GRAMC (Computing Ressource Granting Software)
 * GRAMC stands for : Gestion des Ressources et de leurs Attributions pour Mésocentre de Calcul
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

use App\Entity\Version;
use App\Entity\Projet;
use App\Entity\Session;
use App\Entity\Individu;
use App\Entity\CollaborateurVersion;
use App\Entity\RapportActivite;
use App\Entity\Expertise;
use App\Entity\Thematique;
use App\GramcServices\Workflow\Projet4\Projet4Workflow;
use App\GramcServices\ServiceMenus;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceServeurs;
use App\GramcServices\ServiceNotifications;
use App\GramcServices\ServiceProjets;
use App\GramcServices\ServiceSessions;
use App\GramcServices\ServiceForms;
use App\GramcServices\ServiceVersions;
use App\GramcServices\ServiceRessources;
use App\GramcServices\ServiceDacs;
use App\GramcServices\ServiceExperts\ServiceExperts;
use App\GramcServices\GramcDate;

use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;

use App\Utils\Functions;
use App\GramcServices\Etat;
use App\GramcServices\Signal;
use App\Form\IndividuFormType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\form;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use App\Validator\Constraints\PagesNumber;
use Knp\Snappy\Pdf;
use Twig\Environment;

use Doctrine\ORM\EntityManagerInterface;

/******************************************
 *
 * VersionController = Les contrôleurs utilisés avec les versions de projets
 *                     Partie COMMUNE A TOUS LES MESOCENTRES
 *
 * Voir aussi les fichiers mesocentres/xxx/src/Controller/VersionModifController.php
 * pour des contrôleurs spécifiques à chaque mésocentre
 * (ce qui concerne la modification des versions)
 *
 **********************************************************************/

/**
 * Version controller.
 *
 * @Route("version")
 */
class VersionController extends AbstractController
{
    public function __construct(
        private $dyn_duree,
        private $dyn_duree_post,

        private ServiceRessources $sroc,
        private ServiceDacs $sdac,
        private LoggerInterface $lg,
        private Environment $tw,

        private ServiceNotifications $sn,
        private ServiceJournal $sj,
        private ServiceMenus $sm,
        private ServiceProjets $sp,
        private ServiceServeurs $sr,
        private ServiceSessions $ss,
        private ServiceForms $sf,
        private GramcDate $sd,
        private ServiceVersions $sv,
        private ServiceExperts $se,
        private Projet4Workflow $pw4,
        private FormFactoryInterface $ff,
        private ValidatorInterface $vl,
        private TokenStorageInterface $tok,
        private AuthorizationCheckerInterface $ac,
        private Pdf $pdf,
        private EntityManagerInterface $em
    ) {}

    /**
     * Lists all version entities. CRUD
     * 
     * @Route("/", name="version_index",methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function indexAction(): Response
    {
        $em = $this->em;

        $versions = $em->getRepository(Version::class)->findAll();

        return $this->render('version/index.html.twig', array(
            'versions' => $versions,
        ));
    }

    /**
     * Creates a new version entity. CRUD
     *
     * @Route("/new", name="version_new",methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function newAction(Request $request): Response
    {
        $version = new Version();
        $form = $this->createForm('App\Form\VersionType', $version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($version);
            $em->flush($version);

            return $this->redirectToRoute('version_show', array('id' => $version->getId()));
        }

        return $this->render('version/new.html.twig', array(
            'version' => $version,
            'form' => $form->createView(),
        ));
    }

    /**
     * Affichage d'un écran de confirmation avant la suppression d'une version de projet
     *
     * @Route("/{id}/avant_supprimer",
     *        name="version_avant_supprimer",
     *        methods={"GET"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     * Method("GET")
     *
     */
    public function avantSupprimerAction(Version $version): Response
    {
        $sm = $this->sm;
        $sj = $this->sj;

        // ACL
        if ($sm->modifierVersion($version)['ok'] === false) {
            $sj->throwException(__METHOD__ . ':' . __LINE__ . " impossible de supprimer la version " . $version->getIdVersion().
                " parce que : " . $sm->modifierVersion($version)['raison']);
        }

        return $this->render(
            'version/avant_supprimer.html.twig',
            [
                'version' => $version,
            ]
        );
    }

    /**
     * Supprimer version (en base de données et dans le répertoire data)
     *
     * @Route("/{id}/supprimer", name="version_supprimer",methods={"GET"} )
     * @Security("is_granted('ROLE_DEMANDEUR')")
     *
     */
    public function supprimerAction(Version $version): Response
    {
        $em = $this->em;
        $sm = $this->sm;
        $sv = $this->sv;
        $sp = $this->sp;
        $sj = $this->sj;

        // ACL
        if ($sm->modifierVersion($version)['ok'] === false) {
            $sj->throwException(__METHOD__ . ':' . __LINE__ . " impossible de supprimer la version " . $version->getIdVersion().
                " parce que : " . $sm->modifierVersion($version)['raison']);
        }

        $sp->supprimerVersion($version);
        
        return $this->redirectToRoute('projet_accueil');
    }

    /**
     * Supprimer Fichier attaché à une version
     *
     * @Route("/{id}/{filename}/supprimer_fichier", name="version_supprimer_fichier",methods={"GET"} )
     * @Security("is_granted('ROLE_DEMANDEUR')")
     *
     */
    public function supprimerFichierAction(Version $version, string $filename): Response
    {
        $em = $this->em;
        $sm = $this->sm;
        $sv = $this->sv;
        $sj = $this->sj;
        $ac = $this->ac;

        // ACL - Les mêmes que pour supprimer version !
        if ($sm->modifierVersion($version)['ok'] === false) {
            $sj->throwException(__METHOD__ . ':' . __LINE__ . " impossible de supprimer des images de cette version " . $version->getIdVersion().
                " parce que : " . $sm->modifierVersion($version)['raison']);
        }

        $etat = $version->getEtatVersion();
        $idProjet = null;
        $idVersion = null;
        if ($version->getProjet() === null) {
            $idProjet = null;
            $idVersion = $version->getIdVersion();
        } else {
            $idProjet   =  $version->getProjet()->getIdProjet();
        }

        // Seulement en édition demande, ou alors si je suis admin !
        if ($etat === Etat::EDITION_DEMANDE || $ac->isGranted('ROLE_ADMIN'))
        {
            // suppression des fichiers liés à la version
            $sv->effacerFichier($version, $filename);
        }

        return new Response(json_encode("OK $filename"));
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Convertit et affiche la version au format pdf
     *
     * @Route("/{id}/pdf", name="version_pdf",methods={"GET"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     * Method("GET")
     */
    public function pdfAction(Version $version, Request $request): Response
    {
        $sv = $this->sv;
        $sp = $this->sp;
        $sj = $this->sj;
        $spdf = $this->pdf;

        $projet = $version->getProjet();
        if (! $sp->projetACL($projet)) {
            $sj->throwException(__METHOD__ . ':' . __LINE__ .' problème avec ACL');
        }

        $img_expose = [
            $sv->imageProperties('img_expose_1', 'Figure 1', $version),
            $sv->imageProperties('img_expose_2', 'Figure 2', $version),
            $sv->imageProperties('img_expose_3', 'Figure 3', $version),
        ];

        $img_justif_renou = [
            $sv->imageProperties('img_justif_renou_1', 'Figure 1', $version),
            $sv->imageProperties('img_justif_renou_2', 'Figure 2', $version),
            $sv->imageProperties('img_justif_renou_3', 'Figure 3', $version),
        ];
        
        $html4pdf =  $this->render(
            'version/pdf.html.twig',
            [
            'warn_type'          => false,
            'projet'             => $projet,
            'pdf'                => true,
            'version'            => $version,
            'menu'               => null,
            'img_expose'         => $img_expose,
            'img_justif_renou'   => $img_justif_renou,
            'rapport_1'          => null,
            'rapport'            => null,
            ]
        );

        // NOTE - Pour déboguer la version pdf, décommentez 
        //return $html4pdf;
        
        $pdf = $spdf->setOption('enable-local-file-access', true);
        $pdf = $spdf->getOutputFromHtml($html4pdf->getContent());
        return Functions::pdf($pdf);
    }

    /**
     * Téléchargement de la fiche Projet
     *
     * @Route("/{id}/fiche_pdf", name="version_fiche_pdf",methods={"GET"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     * Method("GET")
     */
    public function fichePdfAction(Version $version, Request $request): Response
    {
        $sm   = $this->sm;
        $sj   = $this->sj;
        $spdf = $this->pdf;

        $projet =  $version->getProjet();

        // ACL
        if ($sm->telechargerFiche($version)['ok'] === false) {
            $sj->throwException(__METHOD__ . ':' . __LINE__ . " impossible de télécharger la fiche du projet " . $projet .
                " parce que : " . $sm->telechargerFiche($version)['raison']);
        }

        $html4pdf =  $this->render(
            'version/fiche_pdf.html.twig',
            [
                'projet' => $projet,
                'version'   =>  $version,
                ]
        );
        // return $html4pdf;
        //$html4pdf->prepare($request);
        //$pdf = App::getPDF($html4pdf);
        //$pdf = App::getPDF($html4pdf->getContent());
        $pdf = $spdf->getOutputFromHtml($html4pdf->getContent());

        return Functions::pdf($pdf);
    }

    /**
     * Téléversement de la fiche projet
     *
     * @Route("/{id}/televerser_fiche", name="version_televerser_fiche",methods={"GET","POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     */
    public function televerserFicheAction(Request $request, Version $version): Response
    {
        $em = $this->em;
        $sm = $this->sm;
        $sj = $this->sj;

        // ACL
        if ($sm->televerserFiche($version)['ok'] === false) {
            $sj->throwException(__METHOD__ . ':' . __LINE__ . " impossible de téléverser la fiche de la version " . $version .
                " parce que : " . $sm -> televerserFiche($version)['raison']);
        }

        $rtn = $this->televerser($request, $version, "fiche.pdf");

        // Si on récupère un formulaire on l'affiche
        if (is_a($rtn, 'Symfony\Component\Form\Form'))
        {
            return $this->render(
                'version/televerser_fiche.html.twig',
                [
                    'version' => $version,
                    'form' => $rtn->createView(),
                    'resultat' => null
                ]);
        }

        // Sinon c'est une chaine de caractères en json.
        else
        {
            $resultat = json_decode($rtn, true);

            if ($resultat['OK'])
            {
                $this->modifyFiche($version);
                $request->getSession()->getFlashbag()->add("flash info","La fiche projet a été correctement téléversée");
                return $this->redirectToRoute('consulter_projet', ['id' => $version->getProjet()->getIdProjet() ]);
            }
            else
            {
                $request->getSession()->getFlashbag()->add("flash erreur",strip_tags($resultat['message']));
                return $this->redirectToRoute('version_televerser_fiche', ['id' => $version->getIdVersion() ]);
            }
            return new Response ($rtn);
        }
    }

    /**
     * Téléversement de la fiche projet en tant qu'admin
     *
     * @Route("/{id}/televerser_fiche_admin", name="version_televerser_fiche_admin",methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function televerserFicheAdminAction(Request $request, Version $version): Response
    {
        $em = $this->em;
        $sm = $this->sm;
        $sj = $this->sj;

        $rtn = $this->televerser($request, $version, "fiche.pdf");

        // Si on récupère un formulaire on l'affiche
        if (is_a($rtn, 'Symfony\Component\Form\Form'))
        {
            return $this->render(
                'version/televerser_fiche.html.twig',
                [
                    'version' => $version,
                    'form' => $rtn->createView(),
                    'resultat' => null
                ]);
        }

        // Sinon c'est une chaine de caractères en json.
        else
        {
            $resultat = json_decode($rtn, true);

            if ($resultat['OK'])
            {
                $this->modifyFiche($version);
                $request->getSession()->getFlashbag()->add("flash info","La fiche projet a été correctement téléversée");
                return $this->redirectToRoute('consulter_projet', ['id' => $version->getProjet()->getIdProjet() ]);
            }
            else
            {
                $request->getSession()->getFlashbag()->add("flash erreur",strip_tags($resultat['message']));
                return $this->redirectToRoute('version_televerser_fiche', ['id' => $version->getIdVersion() ]);
            }
            return new Response ($rtn);
        }
    }

    private function modifyFiche(Version $version) : void
    {
        $em = $this->em;
        
        // on marque le téléversement de la fiche projet
        $version->setPrjFicheVal(true);
        $em->persist($version);
        $em->flush();
    }

    /**
     * Changer le responsable d'une version.
     *
     * @Route("/{id}/responsable", name="changer_responsable",methods={"GET","POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     */
    public function changerResponsableAction(Version $version, Request $request): Response
    {
        $sm = $this->sm;
        $sn = $this->sn;
        $sj = $this->sj;
        $sv = $this->sv;
        $ff = $this->ff;
        $token = $this->tok->getToken();

        // ACL
        $moi = $token->getUser();

        if ($version === null) {
            $sj->throwException(__METHOD__ .":". __LINE__ .' version null');
        }

        if ($sm->changerResponsable($version)['ok'] === false) {
            $sj->throwException(__METHOD__ . ":" . __LINE__ .
                    " impossible de changer de responsable parce que " . $sm->changerResponsable($version)['raison']);
        }

        // préparation de la liste des responsables potentiels
        $moi = $token->getUser();
        $collaborateurs = $version->getCollaborateurs(false, true, $moi); // pas moi, seulement les éligibles

        $change_form = Functions::createFormBuilder($ff)
            ->add(
                'responsable',
                EntityType::class,
                [
                    'multiple' => false,
                    'class' => Individu::class,
                    'required'  =>  true,
                    'label'     => '',
                    'choices' =>  $collaborateurs,
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'Nouveau responsable'])
            ->getForm();
        $change_form->handleRequest($request);

        $projet =  $version->getProjet();

        if ($projet != null) {
            $idProjet   =   $projet->getIdProjet();
        } else {
            $sj->errorMessage(__METHOD__ .":". __LINE__ . " projet null pour version " . $version->getIdVersion());
            $idProjet   =   null;
        }

        if ($change_form->isSubmitted() && $change_form->isValid()) {
            $ancien_responsable  = $version->getResponsable();
            $nouveau_responsable = $change_form->getData()['responsable'];
            if ($nouveau_responsable === null) {
                return $this->redirectToRoute('consulter_version', ['id' => $idProjet, 'version' => $version->getId()]);
            }

            if ($ancien_responsable != $nouveau_responsable) {
                $sv->changerResponsable($version, $nouveau_responsable);

                $params = [
                            'ancien' => $ancien_responsable,
                            'nouveau'=> $nouveau_responsable,
                            'version'=> $version
                           ];

                // envoyer une notification à l'ancien et au nouveau responsable
                $sn->sendMessage(
                    'notification/changement_resp_pour_ancien-sujet.html.twig',
                    'notification/changement_resp_pour_ancien-contenu.html.twig',
                    $params,
                    [$ancien_responsable]
                );

                $sn->sendMessage(
                    'notification/changement_resp_pour_nouveau-sujet.html.twig',
                    'notification/changement_resp_pour_nouveau-contenu.html.twig',
                    $params,
                    [$nouveau_responsable]
                );

                $sn->sendMessage(
                    'notification/changement_resp_pour_admin-sujet.html.twig',
                    'notification/changement_resp_pour_admin-contenu.html.twig',
                    $params,
                    $sn->mailUsers(['A'], null)
                );
            }
            return $this->redirectToRoute(
                'consulter_version',
                [
                    'version' => $version->getIdVersion(),
                    'id'    =>  $idProjet,
                ]
            );
        }

        return $this->render(
            'version/responsable.html.twig',
            [
                'projet' => $idProjet,
                'change_form'   => $change_form->createView(),
                'version'   =>  $version,
            ]
        );
    }

    /**
     * Modifier les collaborateurs d'une version.
     *
     * @Route("/{id}/collaborateurs", name="modifier_collaborateurs",methods={"GET","POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     */
    public function modifierCollaborateursAction(Version $version, Request $request): Response
    {
        $sm = $this->sm;
        $sj = $this->sj;
        $sval= $this->vl;
        $sv = $this->sv;
        $sr = $this->sr;
        $em = $this->em;


        if ($sm->modifierCollaborateurs($version)['ok'] === false) {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " impossible de modifier la liste des collaborateurs de la version " . $version .
                " parce que : " . $sm->modifierCollaborateurs($version)['raison']);
        }

        $text_fields = true;
        if ($this->getParameter('resp_peut_modif_collabs'))
        {
            $text_fields = false;
        }

        $collaborateur_form = $this->ff
                                   ->createNamedBuilder('form_projet', FormType::class, [
                                       'individus' => $sv->prepareCollaborateurs($version, $sj, $sval)
                                   ])
                                   ->add('individus', CollectionType::class, [
                                       'entry_type'   =>  IndividuFormType::class,
                                       'label'        =>  false,
                                       'allow_add'    =>  true,
                                       'allow_delete' =>  true,
                                       'prototype'    =>  true,
                                       'required'     =>  true,
                                       'by_reference' =>  false,
                                       'delete_empty' =>  true,
                                       'attr'         => ['class' => "profil-horiz"],
                                       'entry_options' =>['text_fields' => $text_fields,'srv_noms' => $sr->getnoms()],
                                       //'prototype_options' =>['text_fields' => $text_fields,'srv_noms' => $srv_noms],
                                   ])
                                   ->add('submit', SubmitType::class, [
                                        'label' => 'Sauvegarder',
                                        'attr' => ['title' => "Sauvegarder et revenir au projet"],
                                   ])
                                   ->add('annuler', SubmitType::class, [
                                        'label' => 'Annuler',
                                        'attr' => ['title' => "Annuler et revenir au projet"],
                                   ])
                                   ->getForm();

        $collaborateur_form->handleRequest($request);

        $projet =  $version->getProjet();
        if ($projet != null) {
            $idProjet   =   $projet->getIdProjet();
        } else {
            $sj->errorMessage(__METHOD__ .':' . __LINE__ . " : projet null pour version " . $version->getIdVersion());
            $idProjet   =   null;
        }

        if ($collaborateur_form->isSubmitted() && $collaborateur_form->isValid()) {

            // Annuler ou Sauvegarder ?
            if ($collaborateur_form->get('submit')->isClicked())
            {
                // Un formulaire par individu
                $individu_forms =  $collaborateur_form->getData()['individus'];
                $validated = $sv->validateIndividuForms($individu_forms);
                if (! $validated) {
                    $message = "Pour chaque personne vous <strong>devez renseigner</strong>: email, prénom, nom";
                    $request->getSession()->getFlashbag()->add("flash erreur",$message);
                    return $this->redirectToRoute('modifier_collaborateurs', ['id' => $version ]);
                }
                
                // On traite les formulaires d'individus un par un
                $sv->handleIndividuForms($individu_forms, $version);
            }

            // On retourne à la page du projet
            return $this->redirectToRoute('consulter_version', ['id' => $version->getProjet() ]);
        }

        //return new Response( dump( $collaborateur_form->createView() ) );
        return $this->render(
            'version/collaborateurs.html.twig',
            [
             'projet' => $idProjet,
             'collaborateur_form'   => $collaborateur_form->createView(),
             'version'   =>  $version,
         ]
        );
    }

    /**
     * envoyer à l'expert
     *
     * @Route("/{id}/envoyer", name="envoyer_en_expertise",methods={"GET","POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     */
    public function envoyerAction(Version $version, Request $request, LoggerInterface $lg): Response
    {
        $sm = $this->sm;
        $sj = $this->sj;
        $ff = $this->ff;
        $se = $this->se;
        $em = $this->em;

        if ($version->getTypeVersion() === Projet::PROJET_DYN)
        {
            $projetWorkflow = $this->pw4;
        }
        else
        {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " $version n'est PAS de type 4 (type ".$version->getTypeVersion().")");
        }

        if ($sm->envoyerEnExpertise($version)['ok'] === false) {
        $sj->throwException(__METHOD__ . ":" . __LINE__ .
            " impossible d'envoyer le projet parce que " . $sm->envoyerEnExpertise($version)['raison']);
        }

        $projet  = $version->getProjet();

        // NOTE - devrait être CGA plutôt que CGU...
        $form = Functions::createFormBuilder($ff)
                ->add(
                    'CGU',
                    CheckBoxType::class,
                    [
                        'required'  =>  false,
                        'label'     => '',
                    ]
                )
            ->add('envoyer', SubmitType::class, ['label' => "Envoyer le projet"])
            ->add('annuler', SubmitType::class, ['label' => "Annuler"])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $CGU = $form->getData()['CGU'];
            if ($form->get('annuler')->isClicked())
            {
                $request->getSession()->getFlashbag()->add("flash erreur","Votre projet ne nous a pas été envoyé");
                return $this->redirectToRoute('consulter_projet', [ 'id' => $projet->getIdProjet() ]);
            }

            if ($CGU === false && $form->get('envoyer')->isClicked())
            {
                $request->getSession()->getFlashbag()->add("flash erreur","Vous ne pouvez pas envoyer votre projet si vous n'acceptez pas les CGU");
            }
            elseif ($CGU === true && $form->get('envoyer')->isClicked())
            {
                $version->setCGU(true);
                Functions::sauvegarder($version, $em, $lg);

                // Crée une nouvelle expertise
                $se->newExpertiseIfPossible($version);

                // Avance du workflow
                $rtn = $projetWorkflow->execute(Signal::CLK_VAL_DEM, $projet);

                if ($rtn === true)
                {
                    $request->getSession()->getFlashbag()->add("flash info","Votre projet nous a été envoyé. Vous allez recevoir un courriel de confirmation.");
                }
                else
                {
                    $sj->errorMessage(__METHOD__ .  ":" . __LINE__ . " Le projet " . $projet->getIdProjet() . " n'a pas pu etre envoyé à l'expert correctement.");
                    $request->getSession()->getFlashbag()->add("flash erreur","Votre projet n'a pas pu être envoyé en validation. Merci de vous rapprocher du support");
                }
                return $this->redirectToRoute('projet_accueil');
            }
            else
            {
                $request->getSession()->getFlashbag()->add("flash erreur","Votre projet n'a pas pu être envoyé en expertise. Merci de vous rapprocher du support");
                $sj->throwException(__METHOD__ .":". __LINE__ ." Problème avec le formulaire d'envoi à l'expert du projet " . $version->getIdVersion());
            }
        }

        return $this->render(
            'version/envoyer_en_expertise.html.twig',
            [ 'projet' => $projet,
              'form' => $form->createView(),
            ]
        );
    }

    /**
     * Téléversements génériques de rapport d'activité ou de fiche projet
     *
     * @Route("/televersement", name="televersement_generique",methods={"GET","POST"})
     * Method({"POST","GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function televersementGeneriqueAction(Request $request): Response
    {
        $em = $this->em;
        $sd = $this->sd;
        $ss = $this->ss;
        $sp = $this->sp;
        $sj = $this->sj;

        // Premier formulaire = téléversement de la fiche projet
        $form = $this
           ->ff
           ->createNamedBuilder('upload', FormType::class, [], ['csrf_protection' => false ])
           ->add('version', TextType::class, [ 'label'=> "", 'required' => true, 'attr' => ['placeholder' => '01M23999']])
           ->add(
               'type',
               ChoiceType::class,
               [
                'required' => true,
                'choices'  => [
                                "Fiche projet" => "f",
                                "Rapport d'activité" => "r",
                              ],
                'label'    => "",
            ]
           )
           ->add('televerser', SubmitType::class)
           ->add('attribution', SubmitType::class,[ 'label'=> "Changer l'attribution" ] )
           ->getForm();

        $erreurs  = [];
        $resultat = [];

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();

            // Récupérer la version
            if (isset($data['version']) && $data['version'] != null)
            {
                $version = $em->getRepository(Version::class)->find($data['version']);
                if ($version === null)
                {
                    $request->getSession()->getFlashbag()->add("flash erreur","Pas de version " . $data['version']);
                    return $this->redirectToRoute('televersement_generique');
                }
            }
            else
            {
                $request->getSession()->getFlashbag()->add("flash erreur","Erreur interne");
                return $this->redirectToRoute('televersement_generique');
            }

            // Bouton Téléverser
            if ($form->get('televerser')->isClicked())
            {
                if (isset($data['type']) && $data['type'] === 'f')
                {
                    return $this->redirectToRoute('version_televerser_fiche_admin', ['id' => $version->getIdVersion() ]);
                }
                elseif (isset($data['type']) && $data['type'] === 'r')
                {
                    $request->getSession()->getFlashbag()->add("flash erreur","PAS DE RAPPORT D'ACTIVITE dans cette version de gramc-meso");
                    return $this->redirectToRoute('televersement_generique');
                }
                else
                {
                    $request->getSession()->getFlashbag()->add("flash erreur","Erreur interne");
                    return $this->redirectToRoute('televersement_generique');
                }
            }

            // Bouton Attribution
            if ($form->get('attribution')->isClicked())
            {
                //dd('ATTRIB',$version);
                return $this->redirectToRoute('version_attribution_admin', ['id' => $version->getIdVersion() ]);
            }
        }

        return $this->render(
            'version/televersement_generique.html.twig',
            [
            'form'     => $form->createView(),
            'erreurs'  => $erreurs,
            'resultat' => $resultat,
        ]
        );
    }

    /**
     * Changer l'attribution d'une version en mode admin
     *
     * @Route("/{id}/attribution", name="version_attribution_admin",methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function versionAttributionAction(Request $request, Version $version): Response
    {
        $sv = $this->sv;
        $em = $this->em;

        // FORMULAIRE DES RESSOURCES
        $ressource_form = $sv->getRessourceForm($version, true);
        $ressource_form->handleRequest($request);
        $data = $ressource_form->getData();
        $ressource_forms = $data['ressource'];

        $editForm = $this->createFormBuilder($version)
            ->add('enregistrer', SubmitType::class, ['label' => 'Enregistrer' ])
            ->add('fermer', SubmitType::class, ['label' => 'Fermer' ])
            ->add('annuler', SubmitType::class, ['label' => 'Annuler' ])
            ->getForm();

        $erreurs = [];
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted())
        {
            if ($editForm->get('annuler')->isClicked())
            {
                return $this->redirectToRoute('televersement_generique');
            }
            else
            {
                $validated = $sv->validateRessourceForms($ressource_forms);
                if (! $validated)
                {
                    $message = "Erreur dans une de vos attributions";
                    $request->getSession()->getFlashbag()->add("flash erreur",$message);
                }
                else
                {
                    $em->flush();
                    $request->getSession()->getFlashbag()->add("flash info","Attributions enregistrées");
                    if ($editForm->get('fermer')->isClicked()) {
                        return $this->redirectToRoute('televersement_generique');
                    }
                }
            }
        }
        return $this->render(
            'version/attribution.html.twig',
            [
            'version'  => $version,
            'edit_form' => $editForm->createView(),
            'ressource_form' => $ressource_form->createView(),
            'erreurs'   => $erreurs,
            ]);
    }
 
    ///////////////////////////////////////////////////////////////

    /**
     * Téléverser un fichier lié à une version (images, document attaché, rapport d'activité)
     *
     * DOIT ETRE APPELE EN AJAX, Sinon ça NE VA PAS MARCHER !
     *      Renvoie normalement une réponse en json
     *
     * @Route("/{id}/fichier/{filename}", name="televerser",methods={"GET","POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     */
    public function televerserAction(Request $request, version $version, string $filename): Response
    {
        $sm = $this->sm;
        $sj = $this->sj;

        // ACL - Mêmes ACL que modification de version !
        if ($sm->modifierVersion($version)['ok'] === false) {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " impossible de modifier la version " . $version->getIdVersion().
        " parce que : " . $sm->modifierVersion($version)['raison']);
        }

        $rtn = $this->televerser($request, $version, $filename);
        if (is_a($rtn, 'Symfony\Component\Form\Form'))
        {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " Erreur interne - televerser a renvoyé un Form");
        }
        else
        {
            return new Response($rtn);
        }
    }

    /**********************************************************************
     * Fonction unique pour faire le téléversement, que ce soit en ajax ou pas
     *
     ****************************************************/
    private function televerser(Request $request, version $version, string $filename): Form|string
    {
        $sv = $this->sv;
        $sf = $this->sf;
        
        // SEULEMENT CERTAINS NOMS !!!!
        $valid_filenames = ['document.pdf',
                            'rapport.pdf',
                            'fiche.pdf',
                            'img_expose_1',
                            'img_expose_2',
                            'img_expose_3',
                            'img_justif_renou_1',
                            'img_justif_renou_2',
                            'img_justif_renou_3'];

        if (!in_array($filename, $valid_filenames))
        {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " Erreur interne - $filename pas un nom autorisé");
        }

        // Calcul du répertoire et du type de fichier: dépend du nom de fichier
        $dir = "";
        switch ($filename)
        {
            case "document.pdf":
            case "img_expose_1":
            case "img_expose_2":
            case "img_expose_3":
            case "img_justif_renou_1":
            case "img_justif_renou_2":
            case "img_justif_renou_3":
                $dir = $sv->imageDir($version);
                break;
            case "rapport.pdf":
                $dir = $sv->rapportDir($version);
                break;
            case "fiche.pdf":
                $dir = $sv->getSigneDir($version);
                break;
            default:
                $sj->throwException(__METHOD__ . ":" . __LINE__ . " Erreur interne - $filename - calcul de dir pas possible");
                break;
        }

        $type = substr($filename,-3);   // 'pdf' ou ... n'importe quoi !

        // Seulement deux types supportés = pdf ou jpg
        if ($type != 'pdf')
        {
            $type = 'jpg';
        }

        // Traitement différentié pour un rapport:
        // 1/ On CHANGE $filename
        // 2/ On appelle modifyRapport afin d'écrire un truc dans la base de données
        //    On doit le faire ici car on est en ajax et on appelle la fonction "générique" téléverser...
        //
        // TODO: Pas bien joli tout ça...
        if ($filename === 'rapport.pdf')
        {
            $d = basename($dir); // /a/b/c/d/2022 -> 2022
            $filename = $d . $version->getProjet()->getIdProjet() . ".pdf";
            $rtn = $sv->televerserFichier($request, $version, $dir, $filename, $type);
            $resultat = json_decode($rtn, true);
            if ($resultat['OK'])
            {
                $this->modifyRapport($version->getProjet(), $version->anneeRapport(), $filename);
            }
        }

        // Traitement différentié pour une fiche:
        // 1/ On CHANGE $filename
        // 2/ La fonction modifyFiche sera appelée par le controleur televerserFicheAction
        //
        // TODO: Pas bien joli tout ça...
        elseif ($filename === 'fiche.pdf')
        {
            $filename = $sv ->getSignePath($version);
            $rtn = $sv->televerserFichier($request, $version, $dir, $filename, $type);
        }
        else
        {
            $rtn = $sv->televerserFichier($request, $version, $dir, $filename, $type);
        }
        return $rtn;
    }

    ////////////////////////////////////////////////////////////////////

    private function modifyRapport(Projet $projet, string $annee, string $filename): void
    {
        $em = $this->em;
        $sv = $this->sv;

        // création de la table RapportActivite
        $rapportActivite = $em->getRepository(RapportActivite::class)->findOneBy(
            [
            'projet' => $projet,
            'annee' => $annee,
        ]
        );
        if ($rapportActivite === null) {
            $rapportActivite = new RapportActivite($projet, $annee);
        }

        $size = filesize($sv->rapportDir1($projet, $annee) . '/' .$filename );
        $rapportActivite->setTaille($size);
        $em->persist($rapportActivite);
        $em->flush();
    }
    /**
     * Appelé par le bouton Envoyer à l'expert: si la demande est incomplète
     * on envoie un écran pour la compléter. Sinon on passe à envoyer à l'expert
     * TODO - Un héritage ou un interface ici car tous les VersionSpecController ont le même code !
     *
     * @Route("/{id}/avant_modifier", name="avant_modifier_version",methods={"GET","POST"})
     * Method({"GET", "POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     */
    public function avantModifierVersionAction(Request $request, Version $version): Response
    {
        $sm = $this->sm;
        $sj = $this->sj;
        $sv = $this->sv;
        $vl = $this->vl;
        $em = $this->em;

        // ACL
        if ($sm->modifierVersion($version)['ok'] === false) {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " impossible de modifier la version " . $version->getIdVersion().
                " parce que : " . $sm->modifierVersion($version)['raison']);
        }
        if ($sv->validateVersion($version) != []) {
            return $this->render(
                'version/avant_modifier.html.twig',
                [
                'version'   => $version
                ]);
        }
        else
        {
            return $this->redirectToRoute('envoyer_en_expertise', [ 'id' => $version->getIdVersion() ]);
        }
    }

    /**
     * Modification d'une version existante
     *
     *      1/ D'abord une partie générique (images, collaborateurs)
     *      2/ Ensuite on appelle modifierTypeX, car le formulaire dépend du type de projet
     *
     * @Route("/{id}/modifier", name="modifier_version",methods={"GET","POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     * 
     */
    public function modifierAction(Request $request, Version $version): Response
    {
        $sm = $this->sm;
        $sv = $this->sv;
        $sj = $this->sj;
        $twig = $this->tw;
        $html = [];
        
        // ACL
        if ($sm->modifierVersion($version)['ok'] === false) {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " impossible de modifier la version " . $version->getIdVersion().
        " parce que : " . $sm->modifierVersion($version)['raison']);
        }

        // FORMULAIRE DES FORMATIONS
        $formation_form = $sv->getFormationForm($version);
        $formation_form->handleRequest($request);
        $data = $formation_form->getData();
        $formation_forms = $data['formation'];

        // NOTE - $validated peut éventuellement modifier $formation_forms afin de le rendre valide
        $validated = $sv->validateFormationForms($formation_forms);

        $sv->handleFormationForms($data['formation'], $version);

        // FORMULAIRE DES RESSOURCES
        $ressource_form = $sv->getRessourceForm($version);
        $ressource_form->handleRequest($request);
        $data = $ressource_form->getData();
        $ressource_forms = $data['ressource'];

        // NOTE - On met à zéro les demandes qui sont invalides
        $validated = $sv->validateRessourceForms($ressource_forms);
        if (! $validated)
        {
            $message = "Erreur dans une de vos demandes, elle a été mise à 0";
            $request->getSession()->getFlashbag()->add("flash erreur",$message);
        }
        
        // FORMULAIRE DES COLLABORATEURS
        $collaborateur_form = $sv->getCollaborateurForm($version);
        $collaborateur_form->handleRequest($request);
        $data = $collaborateur_form->getData();
        $individu_forms = $data['individus'];
        $validated = $sv->validateIndividuForms($individu_forms);
        if (! $validated)
        {
            $message = "Pour chaque personne vous <strong>devez renseigner</strong>: email, prénom, nom";
            $request->getSession()->getFlashbag()->add("flash erreur",$message);
        }
        else
        {
            if ($data != null && array_key_exists('individus', $data)) {
                $sj->debugMessage('modifierAction traitement des collaborateurs');
                $sv->handleIndividuForms($data['individus'], $version);
    
                // ASTUCE : le mail est disabled en HTML et en cas de POST il est annulé
                // nous devons donc refaire le formulaire pour récupérer ces mails
                $collaborateur_form = $sv->getCollaborateurForm($version);
            }
        }
        
        // DES FORMULAIRES QUI DEPENDENT DU TYPE DE PROJET
        $type = $version->getProjet()->getTypeProjet();
        switch ($type) {
            case Projet::PROJET_DYN:
            return $this->__modifier4($request, $version, $collaborateur_form, $formation_form, $ressource_form);
            default:
               $sj->throwException(__METHOD__ . ":" . __LINE__ . " mauvais type de projet " . Functions::show($type));
        }
    }

    /*
     * Appelée par modifierAction pour les projets de type 4 (PROJET_DYN)
     *
     * params = $request, $version
     *          $image_forms (formulaire de téléversement d'images)
     *          $collaborateurs_form (formulaire des collaborateurs)
     *
     */
    private function __modifier4(Request $request,
                                 Version $version,
                                 FormInterface $collaborateur_form,
                                 FormInterface $formation_form,
                                 FormInterface $ressource_form
                                 ): Response
    {
        $sj = $this->sj;
        $sv = $this->sv;
        $ss = $this->ss;
        $sval = $this->vl;
        $em = $this->em;

        // formulaire principal
        $form_builder = $this->createFormBuilder($version);
        $this->__modifier4PartieI($version, $form_builder);
        $this->__modifier4PartieII($version, $form_builder);
        $this->__modifier4PartieIII($version, $form_builder);
        $nb_form = 0;

        $form_builder
            ->add('fermer', SubmitType::class)
            ->add( 'enregistrer',   SubmitType::Class )
            ->add('annuler', SubmitType::class);

        $form = $form_builder->getForm();
        $form->handleRequest($request);

        // traitement du formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('annuler')->isClicked()) {
                // on ne devrait jamais y arriver !
                // si car j'ai supprimé le truc idiot du haut
                // $sj->errorMessage(__METHOD__ . ' seconde annuler clicked !');
                return $this->redirectToRoute('projet_accueil');
            }

            // on sauvegarde le projet (Enregistrer ou Fermer)
            $return = Functions::sauvegarder($version, $em, $this->lg);

            // Si Enregistrer
            if ($request->isXmlHttpRequest()) {
                $sj->debugMessage(__METHOD__ . ' isXmlHttpRequest clicked');
                if ($return === true) {
                    return new Response(json_encode('OK - Votre projet est correctement enregistré'));
                } else {
                    return new Response(json_encode("ERREUR - Votre projet n'a PAS été enregistré !"));
                }
            }
            return $this->redirectToRoute('consulter_projet', ['id' => $version->getProjet()->getIdProjet() ]);
        }

        $img_expose = [$sv->imageProperties('img_expose_1', 'Figure 1', $version),
                       $sv->imageProperties('img_expose_2', 'Figure 2', $version),
                       $sv->imageProperties('img_expose_3', 'Figure 3', $version)];

        $img_justif_renou = [$sv->imageProperties('img_justif_renou_1', 'Figure 1', $version),
                             $sv->imageProperties('img_justif_renou_2', 'Figure 2', $version),
                             $sv->imageProperties('img_justif_renou_3', 'Figure 3', $version)];

        return $this->render(
            'version/modifier_projet4.html.twig',
            [
                'form' => $form->createView(),
                'version' => $version,
                'img_expose' => $img_expose,
                'img_justif_renou' => $img_justif_renou,
                'collaborateur_form' => $collaborateur_form->createView(),
                'formation_form' => $formation_form->createView(),
                'ressource_form' => $ressource_form->createView(),
                'todo' => $sv->validateVersion($version),
                'nb_form' => $nb_form
            ]
        );
    }

    /* Les champs de la partie I */
    private function __modifier4PartieI($version, &$form): void
    {
        $em = $this->em;
        $form
        ->add('prjTitre', TextType::class, [ 'required'       =>  false ])
        ->add(
            'prjThematique',
            EntityType::class,
            [
            'required'    => false,
            'multiple'    => false,
            'class'       => Thematique::class,
            'label'       => '',
            'placeholder' => '-- Indiquez la thématique',
            ]
        );

        $form
            ->add('prjFinancement', TextType::class, [ 'required'     => false ])
            ->add('prjGenciCentre', TextType::class, [ 'required' => false ])
            ->add('prjGenciMachines', TextType::class, [ 'required' => false ])
            ->add('prjGenciHeures', TextType::class, [ 'required' => false ])
            ->add('prjGenciDari', TextType::class, [ 'required'   => false ]);

        /* Pour un renouvellement, ajouter la justification du renouvellement */
        if (count($version->getProjet()->getVersion()) > 1) {
            $form = $form->add('prjJustifRenouv', TextAreaType::class, [ 'required' => false ]);
        }
    }

    /* Les champs de la partie II */
    private function __modifier4PartieII($version, &$form): void
    {
        $form
        ->add('prjExpose', TextAreaType::class, [ 'required'       =>  false ]);
    }

    /* Les champs de la partie III */
    private function __modifier4PartieIII($version, &$form): void
    {
        $form
        ->add('codeNom', TextType::class, [ 'required'       =>  false ])
        ->add('codeLicence', TextAreaType::class, [ 'required'       =>  false ]);
    }

    /**
     * Renouvellement d'une version
     *
     * @Route("/{id}/renouveler", name="renouveler_version",methods={"GET","POST"})
     * @Security("is_granted('ROLE_DEMANDEUR')")
     * Method({"GET", "POST"})
     */
    public function renouvelerAction(Request $request, Version $version): Response
    {
        $type = $version->getProjet()->getTypeProjet();
        switch ($type) {
            case Projet::PROJET_DYN:
                return $this->__renouveler4($request, $version);

            default:
               $sj->throwException(__METHOD__ . ":" . __LINE__ . " mauvais type de projet " . Functions::show($type));
        }
    }

    /*
     * Appelée par renouvelerAction pour les projets de type 4 (PROJET_DYN)
     *
     * params = $request, $version
     *
     */
    private function __renouveler4(Request $request, Version $version): Response
    {
        $sm = $this->sm;
        $sv = $this->sv;
        $sj = $this->sj;
        $sdac = $this->sdac;
        $sroc = $this->sroc;
        $dyn_duree = $this->dyn_duree;
        $dyn_duree_post = $this->dyn_duree_post;
        $projet_workflow = $this->pw4;
        $sd = $this->sd;
        $em = $this->em;


        // ACL
        if ($sm->renouvelerVersion($version)['ok'] === false)
        {
            $sj->throwException(__METHOD__ . ":" . __LINE__ . " Impossible de renouveler la version " . $version->getIdVersion());
        }

        // Création de la nouvelle version
        $projet = $version->getProjet();
        $version = $sv->creerVersion($projet);
        
        $version->setPrjGenciCentre('');
        $version->setPrjGenciDari('');
        $version->setPrjGenciHeures(0);
        $version->setPrjGenciMachines('');
        $version->setStartDate($sd);
        $version->setPrjJustifRenouv(null);
        $version->setCgu(0);

        // On fixe la date limite à la date d'aujourd'hui + dyn_duree jours, mais c'est provisoire
        // La startDate et la LimitDate seront fixées de manière définitive lorsqu'on validera la version
        $version->setLimitDate($sd->getNew()->add(new \DateInterval($dyn_duree)));

        // Etat initial d'une version
        $version->setEtatVersion(Etat::EDITION_DEMANDE);

        Functions::sauvegarder($version, $em, $this->lg);

        return $this->redirect($this->generateUrl('modifier_version', [ 'id' => $version->getIdVersion() ]));
    }

    /******
     * Incrémentation du numéro de version lors d'un renouvellement
     *********************************************/
    private function __incrNbVersion(string $nbVersion): string
    {
        $n = intval($nbVersion);
        $n += 1;
        return sprintf('%02d', $n);
    }
}
