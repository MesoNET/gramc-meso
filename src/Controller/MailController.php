<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Individu;
use App\Entity\Projet;
use App\Entity\Version;
use App\GramcServices\Etat;
use App\GramcServices\GramcDateTime;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceMenus;
use App\GramcServices\ServiceNotifications;
use App\GramcServices\ServiceProjets;
use App\Utils\Functions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// ///////////////////////////////////////////////////
/**
 * Mail controller.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route(path: 'mail')]
class MailController extends AbstractController
{
    public function __construct(
        private ServiceNotifications $sn,
        private ServiceJournal $sj,
        private ServiceMenus $sm,
        private ServiceProjets $sp,
        private FormFactoryInterface $ff,
        private GramcDateTime $grdt,
        private EntityManagerInterface $em
    ) {
    }

    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_PRESIDENT")'))]
    #[Route(path: '/{id}/mail_to_responsables_fiche', name: 'mail_to_responsables_fiche', methods: ['GET', 'POST'])]
    public function mailToResponsablesFicheAction(Request $request, Session $session): Response
    {
        $em = $this->em;
        $sn = $this->sn;
        $sj = $this->sj;
        $ff = $this->ff;
        $sm = $this->sm;

        // ACL
        if (false == $sm->mailToResponsablesFiche()['ok']) {
            $sj->throwException(__METHOD__.':'.__LINE__.' Action impossible - '.$sm->mailToResponsablesFiche()['raison']);
        }

        // On lit directement les templates de mail pour laisser à l'admin la possibilité de les modifier !
        $sujet = \file_get_contents(__DIR__.'/../../templates/notification/mail_to_responsables_fiche-sujet.html.twig');
        $body = \file_get_contents(__DIR__.'/../../templates/notification/mail_to_responsables_fiche-contenu.html.twig');

        $responsables = $this->getResponsablesFiche($session);

        // Le template d'affichage de la page
        $template = 'mail/mail_to_responsables_fiche.html.twig';

        return $this->mailToResponsablesBody($request, $session, 0, $responsables, $sujet, $body, $template);
    }

    /***********************************************************
     *
     * Renvoie la liste des responsables de projet (et des projets) qui n'ont pas (encore)
     * téléversé leur fiche projet signée pour la session $session
     *
     ************************************************************/
    private function getResponsablesFiche(Session $session): array
    {
        $sj = $this->sj;
        $em = $this->em;
        $responsables = [];

        $all_versions = $em->getRepository(Version::class)->findBy(['session' => $session, 'prjFicheVal' => false]);

        foreach ($all_versions as $version) {
            $projet = $version->getProjet();
            if (null == $projet) {
                $sj->errorMessage(__METHOD__.':'.__LINE__.' version '.$version." n'a pas de projet !");
                continue;
            }

            if (Etat::ACTIF != $version->getEtatVersion() && Etat::EN_ATTENTE != $version->getEtatVersion()) {
                continue;
            }

            $responsable = $version->getResponsable();

            if (null != $responsable) {
                $responsables[$responsable->getId()]['selform'] = $this->getSelForm($responsable)->createView();
                $responsables[$responsable->getId()]['responsable'] = $responsable;
                $responsables[$responsable->getId()]['projets'][$projet->getIdProjet()] = $projet;
            } else {
                $sj->errorMessage(__METHOD__.':'.__LINE__.' version '.$version." n'a pas de responsable !");
            }
        }

        return $responsables;
    }

    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_PRESIDENT")'))]
    #[Route(path: '/{id}/mail_to_responsables_rallonge', name: 'mail_to_responsables_rallonge', methods: ['GET', 'POST'])]
    public function mailToResponsablesRallonge(Request $request, Session $session): Response
    {
        $sj = $this->sj;
        $sm = $this->sm;
        $grdt = $this->grdt;

        // ACL
        if (false == $sm->mailToResponsablesRallonge()['ok']) {
            $sj->throwException(__METHOD__.':'.__LINE__.' Action impossible - '.$sm->mailToResponsablesRallonge()['raison']);
        }

        $responsables = $this->getResponsablesActifs();

        // On lit directement les templates de mail pour laisser à l'admin la possibilité de les modifier !
        $sujet = \file_get_contents(__DIR__.'/../../templates/notification/mail_to_responsables_rallonge-sujet.html.twig');
        $body = \file_get_contents(__DIR__.'/../../templates/notification/mail_to_responsables_rallonge-contenu.html.twig');

        // Le template d'affichage de la page
        $template = 'mail/mail_to_responsables_rallonge.html.twig';

        return $this->mailToResponsablesBody($request, $session, 0, $responsables, $sujet, $body, $template);
    }

    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_PRESIDENT")'))]
    #[Route(path: '/{id}/mail_to_responsables', name: 'mail_to_responsables', methods: ['GET', 'POST'])]
    public function mailToResponsablesAction(Request $request, Session $session): Response
    {
        $sj = $this->sj;
        $sm = $this->sm;

        // ACL
        if (false == $sm->mailToResponsables()['ok']) {
            $sj->throwException(__METHOD__.':'.__LINE__.' Action impossible - '.$sm->mailToResponsables()['raison']);
        }

        $responsables = $this->getResponsables($session);

        // On lit directement les templates de mail pour laisser à l'admin la possibilité de les modifier !
        $sujet = \file_get_contents(__DIR__.'/../../templates/notification/mail_to_responsables-sujet.html.twig');
        $body = \file_get_contents(__DIR__.'/../../templates/notification/mail_to_responsables-contenu.html.twig');

        // Le template d'affichage de la page
        $template = 'mail/mail_to_responsables.html.twig';

        return $this->mailToResponsablesBody($request, $session, 0, $responsables, $sujet, $body, $template);
    }

    /*********************************************
     * Méthode utilisée par les controleurs MailTo...
     ***************************************************/
    private function mailToResponsablesBody(Request $request,
        Session $session,
        int $annee,
        array $responsables,
        string $sujet,
        string $body,
        string $template): Response
    {
        $em = $this->em;
        $sn = $this->sn;
        $sj = $this->sj;
        $ff = $this->ff;

        $nb_msg = 0;
        $nb_projets = 0;

        $sent = false;
        $form = Functions::createFormBuilder($ff)
                    ->add('texte', TextareaType::class, [
                        'label' => ' ',
                        'data' => $body,
                        'attr' => ['rows' => 10, 'cols' => 150]])
                    ->add('submit', SubmitType::class, ['label' => 'Envoyer le message'])
                    ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $body = $form->getData()['texte'];

            foreach ($responsables as $item) {
                $individus[$item['responsable']->getIdIndividu()] = $item['responsable'];
                $selform = $this->getSelForm($item['responsable']);
                $selform->handleRequest($request);
                if (empty($selform->getData()['sel'])) {
                    // $sj->debugMessage( __METHOD__ . $version->getIdVersion().' selection NON');
                    continue;
                }
                $sn->sendMessageFromString(
                    $sujet,
                    $body,
                    ['session' => $session,
                      'projets' => $item['projets'],
                      'responsable' => $item['responsable']],
                    [$item['responsable']]
                );
                ++$nb_msg;
                $nb_projets += count($item['projets']);
                // DEBUG = Envoi d'un seul message
                // break;
            }
            if ($nb_msg) {
                $request->getSession()->getFlashbag()->add('flash info', "$nb_msg message(s) envoyé(s)");
            } else {
                $request->getSession()->getFlashbag()->add('flash erreur', 'Pas de message à envoyer');
            }
        }

        return $this->render(
            $template,
            [
                'nb_msg' => $nb_msg,
                'responsables' => $responsables,
                'nb_projets' => $nb_projets,
                'session' => $session,
                'form' => $form->createView(),
            ]
        );
    }

    /***********************************************************
     *
     * Renvoie la liste des responsables de projets actifs
     * c-à-d de projets ayant consommé plus de 80% du quota
     *
     ************************************************************/
    private function getResponsablesActifs(): array
    {
        $sp = $this->sp;
        $sj = $this->sj;
        $em = $this->em;

        $responsables = [];
        $projets = [];
        $seuil = 80;
        $all_projets = $em->getRepository(Projet::class)->findAll();

        foreach ($all_projets as $projet) {
            if (Etat::TERMINE == $projet->getEtatProjet() || Etat::ANNULE == $projet->getEtatProjet()) {
                continue;
            }

            $derniereVersion = $projet->derniereVersion();
            $versionActive = $projet->getVersionActive();
            if (null == $derniereVersion) {
                continue;
            }
            if (null == $versionActive) {
                continue;
            }
            if (null == $derniereVersion->getSession()) {
                continue;
            }

            $responsable = $derniereVersion->getResponsable();
            if (null == $responsable) {
                continue;
            }

            // Filtre sur la conso !
            $c = $sp->getConsoRessource($projet, 'cpu');
            if (0 == $c[1]) {
                continue;
            }   // quota nul, ne devrait pas arriver !
            $conso = intval(100 * $c[0] / $c[1]);
            if ($conso < 80) {
                continue;
            }  // On ne garde que les projets avec quota >= 80%

            $ind = $responsable->getId();
            $responsables[$ind]['selform'] = $this->getSelForm($responsable)->createView();
            $responsables[$ind]['responsable'] = $responsable;
            $responsables[$ind]['projets'][$projet->getIdProjet()] = $projet;
            if (!isset($responsables[$ind]['max_attr'])) {
                $responsables[$ind]['max_attr'] = 0;
            }
            $attr = $projet->getVersionActive()->getAttrHeures();
            if ($attr > $responsables[$ind]['max_attr']) {
                $responsables[$ind]['max_attr'] = $attr;
            }
        }

        // On trie $responsables en commençant par les responsables qui on le plus d'heures attribuées !
        usort($responsables, 'self::compAttr');

        return $responsables;
    }

    /***
     * Renvoie un formulaire avec une case à cocher, rien d'autre
     *
     *   params  $individu
     *   return  une form
     *
     */
    private function getSelForm(Individu $individu): FormInterface
    {
        $nom = 'selection_'.$individu->getId();

        return $this->get('form.factory')->createNamedBuilder($nom, FormType::class, null, ['csrf_protection' => false])
                                            ->add('sel', CheckboxType::class, ['required' => false, 'label' => ' '])
                                            ->getForm();
    }

    #[Route(path: '/tester', name: 'mail_tester', methods: ['GET', 'POST'])]
    public function testerAction(Request $request): Response
    {
        $em = $this->em;
        $sn = $this->sn;
        $ff = $this->ff;

        /*        $now = $em->getRepository(Param::class)->findOneBy(['cle' => 'now']);
                if ($now == null) {
                    $now = new Param();
                    $now->setCle('now');
                    //$em->persist( $now );
                }

                if ($now->getVal() == null) {
                    $date = new \DateTime();
                } else {
                    $date = new \DateTime($now->getVal());
                }
        */

        //    $defaults = [ 'date' => $date ];
        $form = $ff->createBuilder(FormType::class, [])
                        ->add('addr', TextType::class, ['label' => 'Destinataire'])
                        ->add('Envoyer', SubmitType::class)
                        ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $addr = $form->getData()['addr'];
            $sn->sendTestMessage($addr);

            $request->getSession()->getFlashbag()->add('flash info', "Le mail est parti, on vous laisse vérifier qu'il est bien arrivé !");
        }

        return $this->render(
            'mail/tester.html.twig',
            [
            'form' => $form->createView(),
        ]
        );
    }
}
