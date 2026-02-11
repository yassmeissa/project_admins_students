<?php

namespace App\EventListener;

use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DeprecationWarningListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        // Don't suppress anything, just let Symfony handle it
        // The deprecation warnings are logged separately via monolog configuration
    }
}
