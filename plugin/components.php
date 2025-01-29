<?php

use Nerdcel\OAuth2Mail\OAuth2EMail;

return [
    'email' => function ($kirby, $props, $debug) {
        // If the plugin is not enabled, return the default email component
        if (option('nerdcel.kirby-oauth2-email.enabled') !== true) {
            return new Kirby\Email\PHPMailer($props, $debug);
        }

        return new OAuth2EMail($props, $debug);
    },
];
