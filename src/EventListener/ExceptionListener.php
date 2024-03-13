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
 *  authors : Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

/***
 * class ExceptionListener = Ce service intercepte les exceptions et les traite de la manière suivante:
 *                            - En mode DEBUG, affiche l'exception et sort
 *                            - En mode NON DEBUG, écrit dans le fichier de log ou dans le journal, puis redirige vers la page d'accueil
 *                            - TODO - refaire tout ça de manière symfoniquement correcte !
 *
 *
 **********************/

// src/EventListener/ExceptionListener.php

namespace App\EventListener;

use App\GramcServices\ServiceJournal;
// use App\Exception\UserException;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class ExceptionListener
{
    public function __construct(
        private $kernel_debug,
        private RouterInterface $router,
        private LoggerInterface $logger,
        private ServiceJournal $sj,
        private EntityManagerInterface $em,
        private Environment $twig,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $event->getRequest()->server;

        $exception = $event->getThrowable();
        // dd($exception);

        // En mode debug, on affiche l'exception symfony
        // Commenter cette ligne pour récupérer le comportement de la prod en mode prod !
        if ($this->kernel_debug) {
            return;
        }

        // nous captons des erreurs de la page d'accueil
        if ('/' == $event->getRequest()->getPathInfo()) {
            // ne pas écrire dans le journal quand il y a une exception de Doctrine
            if (!$exception instanceof \PDOException && !$exception instanceof \InvalidArgumentException && !$exception instanceof ConnectionException) {
                $this->sj->errorMessage(__METHOD__.':'.__LINE__.' erreur dans la page / depuis '.$event->getRequest()->headers->get('referer'));
            } else {
                $this->logger->error(__METHOD__.':'.__LINE__.'erreur dans la page / depuis '.$event->getRequest()->headers->get('referer'));
            }
            $response = new Response("<h1>Bienvenue sur gramc</h1> Erreur dans la page d'accueil");
            $event->setResponse($response);

            return;
        }

        // on fait une redirection quand il y a une exception de Doctrine
        if ($exception instanceof \PDOException || $exception instanceof \InvalidArgumentException || $exception instanceof ConnectionException) {
            $response = new RedirectResponse($this->router->generate('maintenance'));
            if ('/maintenance' != $event->getRequest()->getPathInfo()) {
                $event->setResponse($response);
            } else {
                $htmlContent = $this->twig->render('default/maintenance.html.twig');
                $event->setResponse(new Response($htmlContent));
            }
        }

        // Erreur 404
        elseif ($exception instanceof NotFoundHttpException) {
            // Nous redirigeons vers la page 'accueil' - pas de log
            $response = new RedirectResponse($this->router->generate('error'));
            $event->setResponse($response);
        }

        // comportement général
        else {
            $this->logger->warning('Error to '.$event->getRequest()->getRequestUri(),
                [
                'exception' => $exception,
                'request' => $event->getRequest(),
                ]);

            $this->sj->warningMessage(__METHOD__.':'.__LINE__.' Exception '.get_class($exception).' : '.$exception->getMessage().
                                      '  À partir de URL : '.$event->getRequest()->getPathInfo());

            // Nous redirigeons vers la page d'accueil
            $response = new RedirectResponse($this->router->generate('accueil'));
            $event->setResponse($response);
        }
    }
}
