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

use App\Entity\Expertise;
use App\Entity\Projet;
use App\Entity\Rallonge;
use App\Entity\Version;
use App\GramcServices\Etat;
use App\GramcServices\GramcDate;
use App\GramcServices\ServiceExpertises;
use App\GramcServices\ServiceExperts\ServiceExperts;
use App\GramcServices\ServiceIndividus;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceMenus;
use App\GramcServices\ServiceNotifications;
use App\GramcServices\ServiceProjets;
use App\GramcServices\ServiceSessions;
use App\GramcServices\ServiceVersions;
use App\GramcServices\Signal;
use App\GramcServices\Workflow\Projet4\Projet4Workflow;
use App\GramcServices\Workflow\Rallonge4\Rallonge4Workflow;
use App\Utils\Functions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Expertise controller.
 */
#[Route(path: 'expertise')]
class ExpertiseController extends AbstractController
{
    private $token;

    public function __construct(
        private $dyn_duree,
        private ServiceNotifications $sn,
        private ServiceJournal $sj,
        private ServiceIndividus $sid,
        private ServiceProjets $sp,
        private ServiceSessions $ss,
        private ServiceMenus $sm,
        private GramcDate $grdt,
        private ServiceVersions $sv,
        private ServiceExpertises $sexp,
        private ServiceExperts $se,
        private Projet4Workflow $p4w,
        private Rallonge4Workflow $r4w,
        private FormFactoryInterface $ff,
        private ValidatorInterface $vl,
        private TokenStorageInterface $tok,
        private AuthorizationCheckerInterface $ac,
        private EntityManagerInterface $em
    ) {
        $this->token = $tok->getToken();
    }

    // /////////////////////

    private static function cmpVersionsByEtat(Version $a, Version $b): int
    {
        return Etat::cmpEtatExpertise($a->getEtatVersion(), $b->getEtatVersion());
    }

    /**
     * Afficher une expertise.
     *
     */
    #[isGranted('ROLE_VALIDEUR')]
    #[Route(path: '/consulter/{id}', name: 'consulter_expertise', methods: ['GET'])]
    public function consulterAction(Request $request, Expertise $expertise): Response
    {
        $token = $this->token;
        $sm = $this->sm;

        $menu[] = $sm->expert();

        $moi = $token->getUser();
        $version = $expertise->getVersion();
        if (null !== $version && $version->isExpertDe($moi)) {
            return $this->render('expertise/consulter.html.twig', ['expertise' => $expertise, 'menu' => $menu]);
        } else {
            return new RedirectResponse($this->generateUrl('accueil'));
        }
    }

    // Helper function used by listeAction
    private static function exptruefirst($a, $b): int
    {
        if (true === $a['expert'] && false === $b['expert']) {
            return -1;
        }
        if ($a['projetId'] < $b['projetId']) {
            return -1;
        }

        return 1;
    }

    /**
     * Liste les projets dynamiques non encore validés.
     *
     */
    #[isGranted('ROLE_VALIDEUR')]
    #[Route(path: '/listedyn', name: 'expertise_liste_dyn', methods: ['GET'])]
    public function listeDynAction(): Response
    {
        $grdt = $this->grdt;
        $sid = $this->sid;
        $ss = $this->ss;
        $sp = $this->sp;
        $sj = $this->sj;
        $token = $this->token;
        $em = $this->em;

        $moi = $token->getUser();
        if (is_string($moi)) {
            $sj->throwException();
        }

        if (null !== $token) {
            $individu = $token->getUser();
            if (!$sid->validerProfil($individu)) {
                return $this->redirectToRoute('profil');
            }
        }

        $mes_thematiques = $moi->getThematique();
        $expertiseRepository = $em->getRepository(Expertise::class);

        // Les expertises affectées à cet expert
        // On regarde toutes les sessions (il peut y avoir des projets fil de l'eau qui trainent)
        // mais seulement les expertises non terminées
        $expertises = $expertiseRepository->findExpertisesDyn();

        // /////////////////////

        return $this->render('expertise/dyn.html.twig',
            ['expertises' => $expertises]);
    }

    // Helper function used by modifierAction
    private static function expprjfirst($a, $b): int
    {
        if ($a === $b) {
            return 0;
        }

        // rallonge - version
        if (null === $a->getVersion() && null !== $b->getVersion()) {
            return 1;
        }

        // version - rallonge
        if (null !== $a->getVersion() && null === $b->getVersion()) {
            return -1;
        }

        // version - version
        if (null !== $a->getVersion()) {
            if ($a->getVersion()->getProjet()->getId() < $b->getVersion()->getId()) {
                return -1;
            } else {
                return 1;
            }
        }

        // rallonge - rallonge
        if (null !== $a->getRallonge()) {
            if ($a->getRallonge()->getVersion()->getProjet()->getId() < $b->getRallonge()->getVersion()->getId()) {
                return -1;
            } else {
                return 1;
            }
        }

        // Ne devrait jamais arriver là
        throw new \Exception('OUPS - Expertise mauvaise');
    }

    /**
     * Le valideur vient de cliquer sur le bouton "Modifier expertise"
     * Il entre son expertise et éventuellement l'envoie.
     *
     * ATTENTION - La même fonction permet de valider PROJETS ET RALLONGES
     *
     */
    #[isGranted('ROLE_VALIDEUR')]
    #[Route(path: '/{id}/modifier', name: 'expertise_modifier', methods: ['GET', 'POST'])]
    public function modifierAction(Request $request, Expertise $expertise): Response
    {
        $ss = $this->ss;
        $sv = $this->sv;
        $sp = $this->sp;
        $sj = $this->sj;
        $ac = $this->ac;
        $grdt = $this->grdt;
        $sval = $this->vl;
        $sexp = $this->sexp;
        $token = $this->token;
        $em = $this->em;

        // ACL et autres controles
        $moi = $token->getUser();

        $version = $expertise->getVersion();
        $rallonge = $expertise->getRallonge();
        $expRallonge = false;
        if (null !== $rallonge) {
            $expRallonge = true;
            $version = $rallonge->getVersion();
        }
        if (null === $version && null === $rallonge) {
            $sj->throwException(__METHOD__.':'.__LINE__.'  '.$expertise." n'a pas de version !");
        }
        if (is_string($moi)) {
            $sj->throwException(__METHOD__.':'.__LINE__.' personne connecté');
        }

        $redirect_to_route = 'expertise_liste_dyn';

        // Si expertise déjà faite on revient à la liste
        if ($expertise->getDefinitif()) {
            $request->getSession()->getFlashbag()->add('flash erreur', 'Version ou rallonge déjà validée !');

            return $this->redirectToRoute($redirect_to_route);
        }

        $expertiseRepository = $em->getRepository(Expertise::class);
        $anneeCour = intval($grdt->format('Y'));
        $anneePrec = $anneeCour - 1;

        // Version est-elle nouvelle ?
        $isnouvelle = $sv->isNouvelle($version);
        $projet = $version->getProjet();
        $projet_type = $projet->getTypeProjet();
        if (Projet::PROJET_DYN !== $projet_type) {
            $sj->throwException(__METHOD__.':'.__LINE__." Le projet $projet n'est pas un projet dynamique (type=$projet_type)");
        }

        // $peut_envoyer -> Si true, on affiche le bouton Envoyer
        $peut_envoyer = true;

        // Création du formulaire
        $editForm = $this->createFormBuilder($expertise)
            ->add('commentaireInterne', TextAreaType::class, ['required' => false])
            ->add('commentaireExterne', TextAreaType::class, ['required' => false])
            ->add(
                'validation',
                ChoiceType::class,
                [
                    'multiple' => false,
                    'choices' => ['Accepter' => 1, 'Refuser' => 0],
                ],
            )
            ->add('enregistrer', SubmitType::class, ['label' => 'Enregistrer'])
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer'])
            ->add('annuler', SubmitType::class, ['label' => 'Annuler'])
            ->add('fermer', SubmitType::class)
            ->getForm();

        $editForm->handleRequest($request);

        // Le formulaire des ressources
        $ressource_form = $expRallonge ? $sexp->getRessourceFormForRallonge($rallonge) : $sexp->getRessourceFormForVersion($version);
        $ressource_form->handleRequest($request);

        // Bouton ANNULER
        if ($editForm->isSubmitted() && $editForm->get('annuler')->isClicked()) {
            return $this->redirectToRoute($redirect_to_route);
        }

        // Boutons ENREGISTRER, FERMER ou ENVOYER
        $erreur = 0;
        $erreurs = [];
        if ($editForm->isSubmitted()) {
            $erreurs = Functions::dataError($sval, $expertise);

            // Projet dynamique = Dès qu'on enregistre une expertise, on est enregistré comme valideur
            $expertise->setExpert($moi);

            $em->persist($expertise);
            $em->flush();
            // dd($expertise);

            // Bouton FERMER
            if ($editForm->get('fermer')->isClicked()) {
                return $this->redirectToRoute($redirect_to_route);
            }

            // Bouton ENVOYER --> Vérification des champs non renseignés
            //                    Si refus, on met toutes les attributions à zéro
            //                    Puis demande de confirmation
            if ($peut_envoyer && $editForm->get('envoyer')->isClicked() && [] === $erreurs) {
                if (false === $expertise->getValidation()) {
                    if ($expRallonge) {
                        $dars = $ressource_form->getData()['ressource'];
                        foreach ($dars as $d) {
                            $d->setAttribution(0);
                            $em->persist($d);
                        }
                        $em->flush();
                    } else {
                        $dacs = $ressource_form->getData()['ressource'];
                        foreach ($dacs as $d) {
                            $d->setAttribution(0);
                            $em->persist($d);
                        }
                        $em->flush();
                    }
                }

                return $this->redirectToRoute('expertise_validation', ['id' => $expertise->getId()]);
            }
        }

        $twig = 'expertise/modifier_projet_dyn.html.twig';
        $expertises = $expertiseRepository->findExpertisesDyn();
        uasort($expertises, 'self::expprjfirst');

        if (0 != count($expertises)) {
            $k = array_search($expertise, $expertises);
            if (0 == $k) {
                $prev = null;
            } else {
                $prev = $expertises[$k - 1];
            }
            $next = null;
            if ($k == count($expertises) - 1) {
                $next = null;
            } else {
                $next = $expertises[$k + 1];
            }
        } else {
            $prev = null;
            $next = null;
        }

        return $this->render(
            $twig,
            [
                'exprallonge' => $expRallonge,
                'isNouvelle' => $isnouvelle,
                'expertise' => $expertise,
                'version' => $version,
                'rallonge' => $rallonge,
                'edit_form' => $editForm->createView(),
                'ressource_form' => $ressource_form->createView(),
                'anneePrec' => $anneePrec,
                'anneeCour' => $anneeCour,
                'peut_envoyer' => $peut_envoyer,
                'erreurs' => $erreurs,
                'prev' => $prev,
                'next' => $next,
                'rapport' => null,
                'document' => null,
            ]
        );
    }

    /**
     * L'expert vient de cliquer sur le bouton "Envoyer expertise"
     * On lui envoie un écran de confirmation.
     *
     */
    #[isGranted('ROLE_VALIDEUR')]
    #[Route(path: '/{id}/valider', name: 'expertise_validation', methods: ['GET', 'POST'])]
    public function validationAction(Request $request, Expertise $expertise): Response
    {
        $dyn_duree = $this->dyn_duree;
        $sn = $this->sn;
        $sj = $this->sj;
        $ac = $this->ac;
        $sp = $this->sp;
        $p4w = $this->p4w;
        $grdt = $this->grdt;
        $em = $this->em;
        $token = $this->token;

        $redirect_to_route = 'expertise_liste_dyn';
        $twig = 'expertise/valider_projet_dyn.html.twig';

        $version = $expertise->getVersion();
        $rallonge = $expertise->getRallonge();
        $expRallonge = false;
        if (null !== $rallonge) {
            $expRallonge = true;
            $twig = 'expertise/valider_rallonge_projet_dyn.html.twig';
            $version = $rallonge->getVersion();
        }
        if (null === $version && null === $rallonge) {
            $sj->throwException(__METHOD__.':'.__LINE__.'  '.$expertise." n'a pas de version !");
        }

        // ACL
        $moi = $token->getUser();
        if (is_string($moi)) {
            $sj->throwException(__METHOD__.':'.__LINE__.' personne connecté');
        } elseif (null === $expertise->getExpert()) {
            $sj->throwException(__METHOD__.':'.__LINE__." aucun expert pour l'expertise ".$expertise);
        } elseif (!$expertise->getExpert()->isEqualTo($moi)) {
            $sj->throwException(__METHOD__.':'.__LINE__.'  '.$moi.
                " n'est pas un expert de l'expertise ".$expertise.", c'est ".$expertise->getExpert());
        }

        $editForm = $this->createFormBuilder($expertise)
                    ->add('confirmer', SubmitType::class, ['label' => 'Confirmer'])
                    ->add('annuler', SubmitType::class, ['label' => 'Annuler'])
                    ->getForm();

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted()) {
            // Bouton Annuler
            if ($editForm->get('annuler')->isClicked()) {
                return $this->redirectToRoute('expertise_modifier', ['id' => $expertise->getId()]);
            }

            // Bouton Confirmer
            // On envoie un signal CLK_VAL_EXP_XXX
            if ($expRallonge) {
                $this->validationForRallonge($rallonge, $expertise);
            } else {
                $this->validationForVersion($version, $expertise);
            }

            return $this->redirectToRoute($redirect_to_route);
        }

        // On n'a pas soumis le formulaire
        return $this->render(
            $twig,
            [
            'expertise' => $expertise,
            'rallonge' => $rallonge,
            'version' => $version,
            'edit_form' => $editForm->createView(),
            ]
        );
    }

    // Valider une version
    private function validationForVersion(Version $version, Expertise $expertise): void
    {
        $sj = $this->sj;
        $em = $this->em;
        $sp = $this->sp;
        $workflow = $this->p4w;
        $dyn_duree = $this->dyn_duree;
        $grdt = $this->grdt;

        // On fixe les dates de début à la date de validation
        // On fixe la date limite à la date de validation + 1 an
        $projet = $expertise->getVersion()->getProjet();
        $version->setStartDate($grdt->getNew());
        $version->setLimitDate($grdt->getNew()->add(new \DateInterval($dyn_duree)));
        $projet->setLimitDate($version->getLimitDate());

        // Si la version active existe, on positionne sa date de fin
        $veract = $projet->getVersionActive();
        if (null !== $veract) {
            $veract->setEndDate($grdt);
            $em->persist($veract);
        }

        $validation = $expertise->getValidation();
        $rtn = null;
        $signal = (true === $validation) ? Signal::CLK_VAL_EXP_OK : Signal::CLK_VAL_EXP_KO;

        $rtn = $workflow->execute($signal, $version->getProjet());
        if (true !== $rtn) {
            $sj->errorMessage(__METHOD__.':'.__LINE__.' Transition avec '.Signal::getLibelle($signal)
            .'('.$signal.") pour l'expertise ".$expertise.' avec rtn = '.Functions::show($rtn));

            return;
        } else {
            $expertise->setDefinitif(true);

            // Version refusée = On met la date de fin à aujourd'hui'
            if (!$validation) {
                $version->setEndDate($grdt->getNew());
                $version->setLimitDate($grdt->getNew());
            }
        }

        // On met à jour la version active
        $sp->versionActive($projet);
        $em->persist($expertise);
        $em->persist($version);
        $em->persist($projet);
        $em->flush();
    }

    // Valider une rallonge
    private function validationForRallonge(Rallonge $rallonge, Expertise $expertise): void
    {
        $sj = $this->sj;
        $workflow = $this->r4w;
        $em = $this->em;

        $validation = $expertise->getValidation();
        $rtn = null;
        $signal = (true === $validation) ? Signal::CLK_VAL_EXP_OK : Signal::CLK_VAL_EXP_KO;

        $rtn = $workflow->execute($signal, $rallonge);
        if (true !== $rtn) {
            $sj->errorMessage(__METHOD__.':'.__LINE__.' Transition avec '.Signal::getLibelle($signal)
            .'('.$signal.") pour l'expertise ".$expertise.' avec rtn = '.Functions::show($rtn));

            return;
        } else {
            $expertise->setDefinitif(true);
        }

        $em->persist($expertise);
        $em->persist($rallonge);
        $em->flush();
    }
}
