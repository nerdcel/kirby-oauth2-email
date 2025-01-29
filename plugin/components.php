<?php

use Nerdcel\OAuth2Mail\OAuth2EMail;

return [
    'email' => function ($kirby, $props, $debug) {
        return new OAuth2EMail($props, $debug);
    }
];
