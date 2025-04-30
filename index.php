<?php

@include_once __DIR__.'/vendor/autoload.php';

if (! function_exists('logIt')) {
    function logIt($message, $context = []): void
    {
        if (kirby()->option('debug') !== true) {
            return;
        }
        $log = Nerdcel\OAuth2Email\Log::singleton('kirby-oauth-2-email');
        $log->it($message, $context);
    }
}

Kirby::plugin('nerdcel/kirby-oauth2-email', [
    'routes' => require __DIR__.'/plugin/routes.php',

    'options' => require __DIR__.'/plugin/options.php',

    'components' => require __DIR__.'/plugin/components.php',
]);
