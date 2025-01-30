<?php

use Nerdcel\OAuth2Email\GetToken;

return function ($kirby) {
    return [
        [
            'pattern' => option('nerdcel.kirby-oauth2-email.callback-path'),
            'method' => 'GET|POST',
            'action' => function () {
                $response = new GetToken();
                return $response->run();
            },
        ],
    ];
};
