<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Notifier\Event\MessageEvent;

class NotificationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => [
                ['browserMessageProcess', 10],
            ],
        ];
    }

    public function processException(ExceptionEvent $event): void
    {
        // ...
    }

    public function logException(ExceptionEvent $event): void
    {
        // ...
    }

    public function notifyException(ExceptionEvent $event): void
    {
        // ...
    }

    public function browserMessageProcess(MessageEvent $event)
    {
        if ('browser' == $event->getMessage()->getTransport()) {
        }
    }
}
