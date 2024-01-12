<?php

// Ajout de headers de sécurité à CHAQUE REPONSE
// cf. https://lindevs.com/add-custom-header-to-every-response-in-symfony/

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [ResponseEvent::class => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (str_starts_with($request->getPathInfo(), '/adminux')) {
            $api = true;
        } else {
            $api = false;
        }
        $response = $event->getResponse();
        if ($api) {
            // Disable the loading of any resources and disable framing, recommended for APIs to use
            $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
        } else {
            $response->headers->set('Content-Security-Policy', "img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-hashes' https://malsup.github.io/jquery.form.js; frame-ancestors 'none'; frame-src 'none'");
            // $response->headers->set("Content-Security-Policy", "default-src 'none'; img-src 'self' data:; style-src 'self' 'unsafe-hashes'; script-src 'self' 'unsafe-hashes'; frame-ancestors 'none'; frame-src 'none'");
        }
        // dd($response);
    }
}
