<?php

namespace Nerdcel\OAuth2Email;

/**
 * Aliases for League Provider Classes
 * Make sure you have added these to your composer.json and run `composer install`
 * Plenty to choose from here:
 * @see https://oauth2-client.thephpleague.com/providers/thirdparty/
 */

//@see https://github.com/thephpleague/oauth2-google
use Kirby\Cms\App;
use League\OAuth2\Client\Provider\Google;

//@see https://packagist.org/packages/hayageek/oauth2-yahoo
use Hayageek\OAuth2\Client\Provider\Yahoo;

//@see https://github.com/stevenmaguire/oauth2-microsoft
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;

//@see https://github.com/greew/oauth2-azure-provider
use Greew\OAuth2\Client\Provider\Azure;

class GetToken
{
    private App $kirby;
    private ?string $provider;
    private ?string $code;
    private ?string $clientId;
    private ?string $clientSecret;
    private ?string $tenantId;

    public function __construct()
    {
        $this->kirby = App::instance();

        $this->code = $this->kirby->request()->get('code');
        $this->provider = $this->kirby->request()->get('provider');
        $this->clientId = $this->kirby->request()->get('clientId');
        $this->clientSecret = $this->kirby->request()->get('clientSecret');
        $this->tenantId = $this->kirby->request()->get('tenantId');
    }

    /**
     * @return string|null
     */
    public function run(): ?string
    {
        if (! isset($this->code) && ! isset($this->provider)) {
            return $this->renderForm();
        }

        return $this->receive();
    }

    /**
     * Render the form for selecting the provider
     *
     * @return string
     */
    private function renderForm(): string
    {
        return <<<'HTML'
            <form method="post">
                <h1>Select Provider</h1>
                <input type="radio" name="provider" value="Google" id="providerGoogle">
                <label for="providerGoogle">Google</label><br>
                <input type="radio" name="provider" value="Yahoo" id="providerYahoo">
                <label for="providerYahoo">Yahoo</label><br>
                <input type="radio" name="provider" value="Microsoft" id="providerMicrosoft">
                <label for="providerMicrosoft">Microsoft</label><br>
                <input type="radio" name="provider" value="Azure" id="providerAzure">
                <label for="providerAzure">Azure</label><br>
                <h1>Enter id and secret</h1>
                <p>These details are obtained by setting up an app in your provider's developer console.
                </p>
                <p>ClientId: <input type="text" name="clientId"><p>
                <p>ClientSecret: <input type="text" name="clientSecret"></p>
                <p>TenantID (only relevant for Azure): <input type="text" name="tenantId"></p>
                <input type="submit" value="Continue">
            </form>
        HTML;
    }

    public function receive(): string
    {
        $session = $this->kirby->session();

        $providerName = '';
        $clientId = '';
        $clientSecret = '';
        $tenantId = '';

        if ($this->provider) {
            $providerName = $this->provider;
            $clientId = $this->clientId;
            $clientSecret = $this->clientSecret;
            $tenantId = $this->tenantId;
            $session->set('provider', $providerName);
            $session->set('clientId', $clientId);
            $session->set('clientSecret', $clientSecret);
            $session->set('tenantId', $tenantId);
        } elseif ($session->get('provider')) {
            $providerName = $session->get('provider');
            $clientId = $session->get('clientId');
            $clientSecret = $session->get('clientSecret');
            $tenantId = $session->get('tenantId');
        }

        $redirectUri = url(option('nerdcel.kirby-oauth2-email.callback_path'));

        $params = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUri,
            'accessType' => 'offline',
        ];

        $options = [];
        $provider = null;

        switch ($providerName) {
            case 'Google':
                $provider = new Google($params);
                $options = [
                    'scope' => [
                        'https://mail.google.com/',
                    ],
                ];
                break;
            case 'Yahoo':
                $provider = new Yahoo($params);
                break;
            case 'Microsoft':
                $provider = new Microsoft($params);
                $options = [
                    'scope' => [
                        'wl.imap',
                        'wl.offline_access',
                    ],
                ];
                break;
            case 'Azure':
                $params['tenantId'] = $tenantId;

                $provider = new Azure($params);
                $options = [
                    'scope' => [
                        'https://outlook.office.com/SMTP.Send',
                        'offline_access',
                    ],
                ];
                break;
        }

        if (null === $provider) {
            return 'Provider missing';
        }

        if (! isset($_GET['code'])) {
            $authUrl = $provider->getAuthorizationUrl($options);
            $session->set('oauth2state', $provider->getState());
            go($authUrl);
        } elseif (empty($_GET['state']) || ($_GET['state'] !== $session->get('oauth2state'))) {
            $session->remove('oauth2state');
            $session->remove('provider');

            return 'Invalid state';
        } else {
            $session->remove('provider');
            $token = $provider->getAccessToken(
                'authorization_code',
                [
                    'code' => $_GET['code'],
                ]
            );

            return 'Refresh Token: '.htmlspecialchars($token->getRefreshToken());
        }
    }
}
