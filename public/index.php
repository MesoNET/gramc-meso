<?php

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

ini_set('max_execution_time', 0);
require dirname(__DIR__).'/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

// Environnement dev = Le fichier adresses.txt contient la liste des adresses autorisée
// ATTENTION - En environnement prod le fichier adresses.txt n'est pas utilisé

// Le fichier adresses.txt contient la liste des adresses autorisées
// cf. adresses.txt.dist pour un modèle de fichier

if ('dev' == $_SERVER['APP_ENV']) {
    $adresses_ip = file(__DIR__.'/../config/adresses.txt');
    $adresses_ip = array_map('trim', $adresses_ip);
    if (false === $adresses_ip) {
        $adresses_ip = [];
    }

    // A AJUSTER SUIVANT QUE VOUS ETES DERRIERE UN PROXY OU NON
    if (isset($_SERVER['HTTP_CLIENT_IP'])
    //    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])    // A COMMENTER DERRIER UN PROXY !
        || !(in_array(@$_SERVER['HTTP_X_REAL_IP'], $adresses_ip) || 'cli-server' === php_sapi_name())) {
        header('HTTP/1.0 403 Forbidden');
        exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information. Your address is '.@$_SERVER['HTTP_X_REAL_IP']);
    }
} else {
    // A AJUSTER SUIVANT QUE VOUS ETES DERRIERE UN PROXY OU NON
    if (isset($_SERVER['HTTP_CLIENT_IP'])
    //    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])    // A COMMENTER DERRIER UN PROXY !
        || 'cli-server' === php_sapi_name()) {
        header('HTTP/1.0 403 Forbidden');
        exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information. Your address is '.@$_SERVER['HTTP_X_REAL_IP']);
    }
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();

// Cette ligne est importante si vous êtes derrière un reverse proxy
// Sinon, commentez-là !
// cf. https://symfony.com/doc/5.4/deployment/proxies.html)
Request::setTrustedProxies(explode(',', $_ENV['TRUSTED_PROXIES']), Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PREFIX);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
