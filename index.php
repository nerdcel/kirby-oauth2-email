<?php

@include_once __DIR__.'/vendor/autoload.php';

load([
    'Nerdcel\\OAuth2Mail\\OAuth2EMail' => 'src/classes/OAuth2EMail.php',
    'Nerdcel\\OAuth2Mail\\GetToken' => 'src/classes/GetToken.php',
], __DIR__);

Kirby::plugin('nerdcel/kirby-oauth2-email', [
    'routes' => require __DIR__.'/plugin/routes.php',

    'options' => require __DIR__.'/plugin/options.php',

    'components' => require __DIR__.'/plugin/components.php',
]);
