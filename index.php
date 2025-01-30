<?php

@include_once __DIR__.'/vendor/autoload.php';

Kirby::plugin('nerdcel/kirby-oauth2-email', [
    'routes' => require __DIR__.'/plugin/routes.php',

    'options' => require __DIR__.'/plugin/options.php',

    'components' => require __DIR__.'/plugin/components.php',
]);
