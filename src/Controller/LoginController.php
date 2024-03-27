<?php

namespace App\Controller;

use App\Entity\Individu;
use App\GramcServices\ServiceJournal;
use App\Utils\Functions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Login controller.
 */
class LoginController extends AbstractController
{
    public function __construct(private ServiceJournal $sj,
        private FormFactoryInterface $ff,
        private AuthorizationCheckerInterface $ac,
        private TokenStorageInterface $ts,
        private EntityManagerInterface $em)
    {
    }

    /**
     * Login "remote" - saml2 (shibboleth) ou openid (iam).
     */
    #[Route(path: '/login', name: 'remlogin', methods: ['GET'])]
    public function remLoginAction(Request $request): Response
    {
        $this->sj->InfoMessage("remote login d'un utilisateur");
        if ($request->getSession()->has('url')) {
            return $this->redirect($request->getSession()->get('url'));
        } else {
            return $this->redirectToRoute('accueil');
        }
    }

    #[Route(path: '/deconnexion', name: 'deconnexion', methods: ['GET'])]
    public function deconnexionAction(Request $request): Response
    {
        $sj = $this->sj;
        $ac = $this->ac;
        $ts = $this->ts;
        $session = $request->getSession();

        // En sudo: on revient à l'utilisateur précédent
        if ($ac->isGranted('IS_IMPERSONATOR')) {
            $sudo_url = $session->get('sudo_url');
            $real_user = $ts->getToken()->getOriginalToken()->getUser();
            $sj->infoMessage(__METHOD__.':'.__LINE__." déconnexion d'un utilisateur en SUDO vers ".$real_user);

            return new RedirectResponse($sudo_url.'?_switch_user=_exit');
        }

        // Pas sudo: on remet token et session à zéro
        elseif ($ac->isGranted('IS_AUTHENTICATED_FULLY')) {
            $sj->infoMessage(__METHOD__.':'.__LINE__." déconnexion de l'utilisateur ".$ts->getToken()->getUser());
            $ts->setToken(null);
            $session->invalidate();

            return new RedirectResponse($this->generateUrl('accueil'));
        }

        // On a cliqué sur Déconnecter alors qu'on n'est pas connecté
        else {
            return new RedirectResponse($this->generateUrl('accueil'));
        }
    }

    #[Route(path: '/erreur_login', name: 'erreur_login', methods: ['GET'])]
    public function erreur_loginAction(Request $request): Response
    {
        return $this->render('login/erreur_login.html.twig');
    }

    #[Route(path: '/login_choice', name: 'connexion', methods: ['GET', 'POST'])]
    public function loginChoiceAction(Request $request): Response
    {
        $sj = $this->sj;
        $ff = $this->ff;

        $mode_auth = $this->getParameter('mode_auth');
        if ('saml2' != $mode_auth) {
            return $this->redirectToRoute('remlogin');
        } else {
            $form = Functions::createFormBuilder($ff)
                    ->add(
                        'data',
                        ChoiceType::class,
                        [
                            'choices' => $this->getParameter('IDPprod'),
                        ]
                    )
                ->add('connect', SubmitType::class, ['label' => 'Connexion *'])
                ->getForm();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $url = $request->getSchemeAndHttpHost();
                $url .= '/Shibboleth.sso/Login?target=';
                $url .= $this->generateUrl('remlogin');

                if ('WAYF' != $form->getData()['data']) {
                    $url = $url.'&providerId='.$form->getData()['data'];
                }

                $sj->debugMessage(__FILE__.':'.__LINE__.' URL remlogin = '.$url);

                return $this->redirect($url);
            }

            return $this->render(
                'default/login.html.twig',
                ['form' => $form->createView()]
            );
        }
    }

    #[Route(path: '/connexion_dbg', name: 'connexion_dbg', methods: ['GET', 'POST'])]
    public function connexion_dbgAction(Request $request): Response
    {
        $ff = $this->ff;

        if (false === $this->getParameter('kernel.debug')) {
            $sj->errorMessage(__METHOD__.':'.__LINE__.' tentative de se connecter connection_debug - Mode DEBUG FALSE');

            return $this->redirectToRoute('accueil');
        }

        // Etablir la liste des users pouvant se connecter de cette manière
        // Tous !
        $repository = $this->em->getRepository(Individu::class);
        /*
        $experts    = $repository->findBy(['expert'   => true ]);
        $valideurs  = $repository->findBy(['valideur' => true ]);
        $admins     = $repository->findBy(['admin'    => true ]);
        $obs        = $repository->findby(['obs'      => true ]);
        $sysadmins  = $repository->findby(['sysadmin' => true ]);
        $users      = array_unique(array_merge($admins, $experts, $valideurs, $obs, $sysadmins));
        */

        // Pour le moment - Tous les utilisateurs
        $users = $repository->findAll();

        // TODO - Il doit y avoir plus élégant
        $choices = [];
        foreach ($users as $u) {
            $choices[$u->getPrenom().' '.$u->getnom()] = $u->getId();
        }
        ksort($choices);

        $form = $ff->createBuilder(FormType::class, null)
            ->add(
                'data',
                ChoiceType::class,
                [
                    'choices' => $choices,
                ]
            )
            ->add('connect', SubmitType::class, ['label' => 'Connexion'])
            ->getForm();

        $form->handleRequest($request);
        // NOTE - Pas de validation du CSRF, ce sera fait par GramcAuthenticator
        //        Donc pas de isValid())
        if ($form->isSubmitted() /* && $form->isValid() */) {
            // Rediriger là où on veut aller
            if ($request->getSession()->has('url')) {
                // dd($request->getSession()->get('url'));
                return $this->redirect($request->getSession()->get('url'));
            }

            // Ou vers l'accueil
            else {
                // dd('accueil');
                return $this->redirectToRoute('accueil');
            }
        }

        return $this->render('login/connexion_dbg.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Sudo (l'admin change d'identité).
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/{id}/sudo', name: 'sudo', methods: ['GET'])]
    public function sudoAction(Request $request, Individu $individu): Response
    {
        $sj = $this->sj;
        $ac = $this->ac;
        if (!$ac->isGranted('IS_IMPERSONATOR')) {
            $session = $request->getSession();
            $sudo_url = $request->headers->get('referer');
            $session->set('sudo_url', $sudo_url);
            $sj->infoMessage("Controller : connexion de l'utilisateur ".$individu.' en SUDO ');

            return new RedirectResponse($this->generateUrl('accueil', ['_switch_user' => $individu->getId()]));
        } else {
            $sj->warningMessage("Controller : connexion de l'utilisateur ".$individu.' déjà en SUDO !');

            return $this->redirectToRoute('individu_gerer');
        }
    }
}
