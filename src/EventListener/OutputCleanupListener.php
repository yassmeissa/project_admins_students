<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OutputCleanupListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 255],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        if (!$response->headers->has('Content-Type') || strpos($response->headers->get('Content-Type'), 'text/html') === false) {
            return;
        }

        $content = $response->getContent();
        
        // Remove all <br /> <b>Deprecated</b>: ... </b><br /> blocks
        $content = preg_replace('/<br\s*\/?>\s*\n?<b>Deprecated<\/b>:\s*[^\n]*(?:\n[^\n]*)*?<\/b><br\s*\/?>/is', '', $content);
        
        $response->setContent($content);
    }
}

