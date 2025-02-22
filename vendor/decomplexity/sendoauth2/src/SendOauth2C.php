<?php
/**
 * SendOauth2C Wrapper for Microsoft and Google OIDC/OAUTH2 For PHPMailer
 * PHP Version 5.5 and greater
 *
 * @category Class
 * @see      https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 * @author   Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
 * @copyright  2021 Max Stewart
 * @license  MIT
 */

namespace decomplexity\SendOauth2;

/**  if autoload fails to load the class-files needed, load them with the following:
require_once 'vendor/thenetworg/oauth2-azure/src/Provider/Azure.php';
require_once 'vendor/league/oauth2-google/src/Provider/Google.php';
require_once 'vendor/google/apiclient/src/Client.php';
require_once 'vendor/google/apiclient-services/src/Gmail.php';
*/

use TheNetworg\OAuth2\Client\Provider\Azure;
use League\OAuth2\Client\Provider\Google;
use Google\Client;
use Google\Service\Gmail;

/**

 * SendOauth2C Class Doc Comment
 *
 * @category Class
 * @package  SendOauth2C
 * @author   Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
 * @license  MIT
 * @note     SendOauth2C is a factory to instantiate an OAuth2 'provider'
 * @note     It supports more than one provider - currently Microsoft and Google -
 * @note     but allows others to be added transparently to SendOauth2A
 * @note     Oauth2 scopes and any overrrides to provider methods are here

  */

class SendOauth2C
{
    /**
     * the Google API  credentials file
     */
    protected $gmailXoauth2Credentials;

    protected $writeGmailCredentialsFile;  // for future use

    /**
     * arbitrary name for the Google API app name used in the HTTP header
     */
    const GOOGLEAPI_APPLICATION_NAME = 'Google Gmail OAuth2 API';

    /**
     * specific grant type
     */
    const CLIENTCRED = 'client_credentials';

    /**
     * the service provider (Microsoft, Google...)
     * @var string
     */
    protected $serviceProvider = "";

    /**
     * authentication type: either CRAM-MD5, LOGIN, PLAIN or XOAUTH2
     */
    protected $authTypeSetting = "";

    /**
     * two parameters which indicate whether or not to generate a refresh token
     * boolean 'refresh' is sent from SendOauthD and SendOauth2B
     * It is decoded into accessPrompt (see below) and accessType (set from SendOauthD
     * as offline and from SendOauth2B as online)
     * SendOauth2D's output is a refresh token, but each time SendOauthB
     * is invoked, we don't always want to generate a new refresh token as well as the
     * access token since Google in particular limits the number of extant refresh tokens
     * and deletes the old ones
     * when we need a refresh token generated, accessPrompt is set to 'consent select_account'
     * which forces a user consent screen  (this is not always needed)
     */
    protected $accessType = "";
    protected $accessPrompt = "";


    /**
     * Instantiation of Oauth2 provider
     * @var string
     */
    public $provider;

    /**
     * scopeAuth is passed to SendOauthD via method getScope
     * for use with getAuthorizationUrl
     * Google will not register the scope request at console.cloud.google.com
     * if it is set as a 'provider' scope (below) either via $this->provider->scope
     * or via a parameter in the instantiation of the provider
     * Note that three default scopes - openid, email and profile - are set automatically
     * and are in the Google project registration
     */

    protected $scopeAuth = "";


    /**
     * Instantiation of PHPMailer
     * @var string
     */
    protected $mail = "";

    /**
     * SMTP server domain name
     */
    protected $SMTPserver = "";


    /**
     * usual OAuth2 app registration details
     */
    protected $tenant;
    protected $clientId;
    protected $clientSecret;
    protected $clientCertificatePrivateKey;
    protected $clientCertificateThumbprint;
    protected $redirectURI;

    /** email address of user to impersonate when using GoogleAPI service account
    * with delegated domain-wide authority
    */
    protected $impersonate;


    /**
    determines whether a refresh token is to be generated
    */
    protected $refresh = "";
   
   
    /**
     * for GSuite accounts only - used to restrict access to a specific domain
     * @var string
     */
    protected $hostedDomain;

    /**
     * Type of grant flow: e.g. authorization_code or client_credentials
     * @var string
     */
    protected $grantType = "";

    /**
     * This should only be set in global if caller wants to override the
     * SMTPAddressDefault set for that switch case in SendOauth2D
     * snd is carried over in the refresh token. When SendOauth2D is run,
     * for MSFT at least the currently logged-on member of the tenant is authorized
     * or a member is requested to log to authorize. This must normally have email address
     * that is the same as thst set in SMTPAddressDefault, otherwise SendOAuth2B authentication fails.
     *
     * @var string
     */
    protected $mailSMTPAddress;

    /**
     * Does the provider support PKCE
     * @var boolean
        */
    protected $isPKCE = true;

    /**
     * __construct Method Doc Comment
     *
     * @category Method
     */

    public function __construct($optionsC)
    {
    /**
     *  check to avoid a PHP 'Notice' message, specially as module is re-entrant
     */
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->tenant = $optionsC['tenant'];
        $this->clientId = $optionsC['clientId'];
        $this->clientSecret = $optionsC['clientSecret'];
        $this->clientCertificatePrivateKey = $optionsC['clientCertificatePrivateKey'];
        $this->clientCertificateThumbprint = $optionsC['clientCertificateThumbprint'];
        $this->redirectURI = $optionsC['redirectURI'];
        $this->serviceProvider = $optionsC['serviceProvider'];
        $this->authTypeSetting = $optionsC['authTypeSetting'];
        $this->hostedDomain = $optionsC['hostedDomain'];
        $this->serviceAccountName = $optionsC['serviceAccountName']; // for future use
        $this->projectID = $optionsC['projectID']; // for future use
        $this->impersonate = $optionsC['impersonate'];
        $this->gmailXoauth2Credentials = $optionsC['gmailXoauth2Credentials'];
        $this->refresh = $optionsC['refresh'];
        $this->grantType = $optionsC['grantType'];
        $this->mailSMTPAddress = $optionsC['mailSMTPAddress'];

    /**
     * authorisation_code grant needs consent value of 'consent'
     * client_credentials grant needs consent value of 'admin_consent'
     */

        $consentType = ($this->grantType == 'authorization_code') ? 'consent' : 'admin_consent';

        switch ($this->refresh) {
            case true:
                $this->accessType = 'offline';
                $this->accessPrompt = $consentType;
                break;

            case false:
                $this->accessType = 'online';
                $this->accessPrompt = 'none';
                break;


    /**
     * ends scope parasmeter switch
     */
        }

        switch ($this->serviceProvider) {
            case "Microsoft":
            default:
                $this->SMTPserver   = 'smtp.office365.com';
    /**
     * don't instantiate the Oauth2 provider unless the authType is XOAUTH2
     */
                if ($this->authTypeSetting != 'XOAUTH2') {
                    break;
                }

    /**
     * Instantiate Jan Hajek's TheNetworg provider for MSFT
     */
                $this->provider = new Azure(
                    [
                    'clientId'                    => $this->clientId,
                    'clientSecret'                => $this->clientSecret,
                    'clientCertificatePrivateKey' => $this->clientCertificatePrivateKey,
                    'clientCertificateThumbprint' => $this->clientCertificateThumbprint,
                    'redirectUri'                 => $this->redirectURI,
                    'accessType'                  => $this->accessType,
                    'prompt'                      => $this->accessPrompt,
                    'defaultEndPointVersion'      => '2.0',
                    ]
                );


    /**
     * Azure provider overrides
     */
                $this->provider->urlAPI = "https://graph.microsoft.com/";
                $this->provider->API_VERSION = '1.0';
                
                 
    /**
     * NB  NB  NB  NB  NB  NB !
     * One change may be needed to provider's oauth2-azure-2.0.0 Azure.php
     * (and perhaps later releases) that cannot be done as an override:
     * At circa line 210, replace graph.windows.net by graph.microsoft.com
     * Depending on the version of TheNetworg provider you are using,
     * both overrides may already be in the code
     */

    /**
     * NB NB  This scope MUST NOT currently  contain any Graph-specific scopes  NB NB
     * else AAD will use Graph as 'aud' claim (resource endpoint) and not outlook.office.com.
     * MSFT 'scope' is quirky and the order of operands is significant
     * See the WiKi document on GitHub in this repo or in PHPMailer repo entitled
     * "Microsoft OAuth2 SMTP issues"
     */

    /**
     * grantType is assumed valid as it is verified in SendOauth2D
     * tenant is needed for client_credentials grant since a specific tenant GUID or domain name must be given;
     * 'common' or 'organizations' or 'consumers' are not valid for client_credentials flow since
     * a user does not log on with CCF
     */
                                
    /**
     * scope is flow dependent, and since with CCF there is no user to log on and consent to scope operands,
     * all permissions set for the app in the Azure portal (the 'default' permissions) are available
     */
                 
                if ($this->grantType == 'authorization_code') {
                    $this->scopeAuth = 'offline_access https://outlook.office.com/SMTP.Send';
                } else {
                    $this->scopeAuth = 'https://outlook.office365.com/.default';
                    $this->provider->tenant = $this->tenant;
                }


    /**
     * The Networg provider does not (March 2024) support PKCE
     */
                $this->isPKCE = false;

                break;
    /**
     * ends MSFT switch case
     */


            case "Google":
                $this->SMTPserver   = 'smtp.gmail.com'; // Google SMTP server
     /**
      * don't instantiate the Oauth2 provider unless the authType is XOAUTH2
      */
                if ($this->authTypeSetting != 'XOAUTH2') {
                    break;
                }
               
                 $this->provider = new Google([
                'clientId'                    => $this->clientId,
                'clientSecret'                => $this->clientSecret,
                'clientCertificatePrivateKey' => $this->clientCertificatePrivateKey,
                'clientCertificateThumbprint' => $this->clientCertificateThumbprint,
                'redirectUri'                 => $this->redirectURI,
                'hostedDomain'                => $this->hostedDomain,
               
     /**
      * note that adding:
      *'scope'  =>  'https://mail.google.com/'
      * here doesn't work - it needs to be in SendOauth2D's $options in
      * $authUrl = $provider->getAuthorizationUrl($options);
      * which is set from $this->scopeAuth below
      */
                'accessType'      =>  $this->accessType,
                'prompt'          =>  $this->accessPrompt
                 ]);

      /**
       * Google scope
       */
                 $this->scopeAuth  = 'openid' . ' ';
                 $this->scopeAuth .= 'https://mail.google.com' . ' ';

      /**
       * note that Google will bounce 'offline_access' as a scope
       */


      /**
       * TheLeague's Google provider does not (March 2024) support PKCE
       */
                $this->isPKCE = false;
                break;


            case "GoogleAPI":
    /**
     * (Basic authentication is not supported by the normal Google API)
     */

                $this->SMTPserver = 'smtp.gmail.com'; // Google SMTP server

                $this->provider  = new Client();
                $this->provider -> setScopes([Gmail::MAIL_GOOGLE_COM]); // must be set before setAuthConfig
                $this->provider -> setAuthConfig($this-> gmailXoauth2Credentials);
                $this->provider -> useApplicationDefaultCredentials();
                $this->provider -> setApplicationName(self::GOOGLEAPI_APPLICATION_NAME);

    /**
     * just in case the caller leaves an email address in impersonate property
     * when switching to authorization_code grant (where setSubject is invalid)
     */
                if ($this->grantType == self::CLIENTCRED) {
                    if ($this->impersonate == "") {
                        $this->impersonate = $this->mailSMTPAddress;
                    }
                    $this->provider -> setSubject($this->impersonate);  // svc accts with domain-wide deleg.
                }

    /**
     * offline/force/consent are needed when using authorization_code flow
     * Google ignores them when using a service account and changes
     * 'offline' to 'online' because there is no refresh token
     */
                $this->provider -> setAccessType('offline');
                $this->provider -> setApprovalPrompt('force');
                $this->provider -> setPrompt('consent');

                $this->scopeAuth =  'https://mail.google.com'; // keep the scopeError check in SendOauth2B happy!

    /**
     * Google API support for PKCE in server-side clients appeared in March 2023
     */
                $this->isPKCE = true;

                break;
    /**
     * ends second switch
     */
        }


    /**
     * ends __construct method
     */
    }
    

    public function getScope()
    {
        return $this->scopeAuth;
    }


    public function setProvider()
    {
        return $this->provider;
    }


    public function setSMTPServer()
    {
        return $this->SMTPserver;
    }

    public function getIsPKCE()
    {
        return $this->isPKCE;
    }

    public function getIsGoogleAPI()
    {
        $x = $this -> serviceProvider == 'GoogleAPI' ? true : false;
        return $x;
    }
 
    /**
     * ends class SendOauth2C
     */
}
