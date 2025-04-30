<?php

use Kirby\Cms\App as Kirby;
use Nerdcel\OAuth2Email\GetToken;

return function (Kirby $kirby) {
    return [
        [
            'pattern' => option('nerdcel.kirby-oauth2-email.callback-path', 'oauth2/callback'),
            'method' => 'GET|POST',
            'action' => function () {
                $response = new GetToken();
                return $response->run();
            },
        ],
    ];
};
