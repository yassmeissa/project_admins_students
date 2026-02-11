<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// Disable error reporting to avoid deprecation warnings in output
ini_set('error_reporting', 0);
ini_set('display_errors', 0);

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
