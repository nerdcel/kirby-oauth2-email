<?php

use Nerdcel\OAuth2Mail\GetToken;

return function ($kirby) {
    return [
        [
            'pattern' => 'email/oauth2',
            'method' => 'GET|POST',
            'action' => function () {
                $response = new GetToken();
                return $response->run();
            },
        ],
    ];
};
