<?php

/*
 * SendOauth2B Wrapper For Microsoft and Google OIDC/OAUTH2 For PHPMailer
 * PHP Version 5.5 and greater
 *
 * @category Class
 * @see      https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 * @author   Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
 * @copyright  2021 Max Stewart
 * @license  MIT
 */
      namespace decomplexity\SendOauth2;

      use PHPMailer\PHPMailer\OAuthTokenProvider;
      use League\OAuth2\Client\Grant\RefreshToken;
      use League\OAuth2\Client\Provider\AbstractProvider;
      use League\OAuth2\Client\Token\AccessToken;
      use Exception;

/**
 * SendOauth2B Class Doc Comment
 * @category Class
 * @package  SendOauth2B
 * @author   Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
 * @license  MIT
 * @note SendOauth2B provides website-wide settings for SendOauth2A and global calling
 * @note supports OAuth2 ('XOAUTH2'), Basic AUTH ('LOGIN') and other AUTHNs
 */

/**
 * implementing OAuthTokenProvider interface ensures that when SMTP.php wants
 * to use GetOauth64(), the oauth instance (in this case $this) has getOauth64() method
 */

class SendOauth2B implements OAuthTokenProvider
{
     use SendOauth2ETrait;
    /**
     *
     * OAUTH2 WEBSITE-WIDE PARAMETERS
     *
     */

    /**
     * constants for grant flow types
     *
     * @var string
     */
    protected const AUTHCODE = 'authorization_code';
    protected const CLIENTCRED = 'client_credentials';

    /**
     * number of arguments passed to constructor that indicate that
     * caller is SendOauth2A and not global code.
     * crude - but saves passing an additional argument
     */
    protected const NUMPARMS = 3;

    /**
     * for GoogleAPI service accounts: a user-specified .json file name
     * but one that will default to the value shown when called from
     * global code. When called from SendOauth2A, it will use a default
     * set in the interchange file by SendOauth2D.
     */

    protected const GMAIL_XOAUTH2_CREDENTIALS = "gmail-xoauth2-credentials.json";

    /**
     * for GoogleAPI service accounts:  a boolean specifying
     * whether oor not to dynamically create the .json credentials file
     */
    protected const WRITE_GMAIL_CREDENTIALS_FILE = 'yes';

    /**
     * Google's API methods are  bit different from MSFT and TheLeagure Google ones
     * Google access tokens are also returned in an array with other unnecessary stuff
     */
    protected const GOOGLE_API = 'GoogleAPI';

  
    /**
     * filename prefix for the parameter file that is passed from running SendOauth2D
     */
    protected const OAUTH2_PARAMETERS_FILE = 'Oauth2parms';

    /**
     * implode/explode array variables separator
     */
    protected const IMPLODE_GLUE = 'IMPLODE_GLUE';

    /**
     * Email address of XOAUTH2 resource owner
     *
     * @var string
     */
    protected $username = '';
    
    /**
     * Tenant GUID or domain name
     * Needed for MSFT client_credentials grant where e.g. 'common' is not allowed
     *
     * @var string
     */
    protected $tenant = '';
    
    /**
     * XOAUTH2 client ID generated by AAD registration
     *
     * @var string
     */
    public $clientId = '';

    /**
     * XOAUTH2 client secret generated by AAD registration
     *
     * @var string
     */
    public $clientSecret = '';

    /**
     * operands for $provider. Fir TheLeague's generic provider, these
     * are technically extensions. Google call the thumbprint the 'private_key_id',
     * otherwise known as a fingerprint. It is typically SHA256 encrypted.
     * In the case you can't remember which private key belong to which public key,
     * the  find the match by comparing their fingerprints
     */
    
    protected $clientCertificatePrivateKey = null;
    protected $clientCertificateThumbprint = null;

    /**
     * Needed for offline generation of the XOAUTH2 refresh token.
     * Should not be needed if the refresh token is an everlasting one
     * Default has correct URI structure for the GoogleAPIOauth2File() method
     * @var string
     */
    protected $redirectURI = 'https://blah.com';

    /**
     * for GSuite accounts only - used to restrict access to a specific domain
     * @var string
     */
    protected $hostedDomain;

    /**
     * for GoogleAPI service accounts only. Not needed for authorization_code grant
     */
    protected $serviceAccountName = "";

    /**
     * for GoogleAPI service accounts only. Not needed for authorization_code grant.
     * Project ID is the lower-case version of the value in the 'footprint'
     * box at the head of an IAM page
     */
    protected $projectID = "";

    /**
     * for GoogleAPI service accounts with delegated domain-wode authority.
     * The email address of the user for the service account to impersonate.
     * if in doubt, use the $mailSMTPAddress value here and in Google Admin
     * console registration, but it will default to that anyway
     */
    protected $impersonate = "";

    /**
     * for GoogleAPI service accounts: a user-specified .json file name
     * but one that will default to the value shown when called from
     * global code. When called from SendOauth2A, it will use a default
     * set in the interchange file by SendOauth2D.
     */
    protected $gmailXoauth2Credentials = "";

    /**
     * for GoogleAPI service accounts:  a yes/no string specifying
     * whether or not to dynamically create the .json
     */
    protected $writeGmailCredentialsFile = "";

    /**
     * XOAUTH2 refresh token initially generated offline by SendOauth2D
     * but dynamically regenerated afterwards
     *
     * @var string
     */
    protected $refreshToken = '';


    /**
     * The current OAuth access token.
     *
     * @var string
     */
    protected $accessToken;

  
    /**
     * scope value passed from SendOauthC
     * needed by PHPMailer Oauth class
     * @var string
     */
    protected $scope = "";

    /**
    *
    * BASIC AUTHENTICAION PARAMETERS
    *
    */

    /**
     * name prefix to sender address; set in SendOuth2D and used by default in SendOuth2A
     * if the caller of SendOauth2A does not specify a fromName
     *
     * @var string
     */
    protected $fromNameDefault = '';

    /**
     * sender email address. Normnally the service provider will mandate that
     * this is a primary address or alias registered as the email address.
     * It is the SMTP 'Userid' and also used for XOAUTH2 authentication
     *
     * @var string
     */
    public $mailSMTPAddress = '';

    /**
     * Basic AUTH password. Not used for Oauth2
     *
     * @var string
     */
    protected $SMTPPassword = '';


    /**
     * Default sender address
     *
     * @var string
     */
    protected $SMTPAddressDefault = '';
     
      
    /**
     * MISC
     */

    /**
     instantiation of PHPMailer
    */
    protected $mail;


    /**
     * instantiation of provider
     */
    protected $provider;

    /**
     * instantiation of SendOauthC
     * @var object
     */
    protected $Send_Oauth_C_obj;


    /**
     * XOAUTH2 or LOGIN - returned to SendOauth2A
     *
     * @var string
     */
    protected $authTypeSetting = "";
    
    /**
     * usually authorization_code grant (for OAuth2 only)
     * set in SendOauth2D-settings. Alternative is 'client_credentials'
     */
    protected $grantType = "";
    
    /**
     * Microsoft, Google or whatever
     *
     * @var string
     */
    protected $serviceProvider = "";

    /**
     * selects the AUTH and service type and parameters - from mainline module
     * or wrapper.
     * defaults to 1 here,even though SendOauth2D-settings also
     * has a default that will override it if the wrapper is used.
     * Some default is needed so that the interchange file can
     * be rewritten with a new refresh token if grantType is
     * authorization_code, and the file name includes
     * mailAuthSet so that different service suppliers and
     * authentication types can coexist
     *
     * @var string
     */
    protected $mailAuthSet = "1";

   
    /**
     * number of arguments  passed when this was instantiated
     * if 3 or less, assume it was from the decomplexity\SendOauth2 wrapper
     * if more, then assume it was instantiated from custom global code
     */
    protected $numArgs = "";


    /**
     * __construct Method Doc Comment
     *
     * @category Method
     * @author     Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
     */

    public function __construct($optionsB)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    
    /**
     * Determine whether we are instantiated from decomplexity\SendOauth2 wrapper
     * or from any custom global caller. The decomplexity\SendOauth2 wrapper
     * normally uses 3 arguments; a global caller must use more than 3 -
     * at least clientID, probably clientSecret, mail (the PHPMAiler instance),
     * serviceProvider (Microsoft, Google...),
     * redirectURI (if authorization_code grant) or grantType (if client_credentials)
     * [grant_type defaults to authorization_code], and authTypeSetting
     * (XOAUTH2 or LOGIN, although CRAM-MD5 and PLAIN are accepted
     * if not desirable!)
     */
     
        $this->numArgs = count($optionsB);
        if ($this->numArgs > self::NUMPARMS) {
            $this->checkParm('tenant', $optionsB, $this->tenant);
            $this->checkParm('clientId', $optionsB, $this->clientId);
            $this->checkParm('clientSecret', $optionsB, $this->clientSecret);
            $this->checkParm('clientCertificatePrivateKey', $optionsB, $this->clientCertificatePrivateKey);
            $this->checkParm('clientCertificateThumbprint', $optionsB, $this->clientCertificateThumbprint);
            $this->checkParm('redirectURI', $optionsB, $this->redirectURI);
            $this->checkParm('serviceProvider', $optionsB, $this->serviceProvider);

    /**
     * authTypeSetting is sent back to caller - global code or SendOauth2A -
     * so that caller can decide whether to try to get a new refresh token if XOAUTH2
     * (not needed for global code since the refresh token will be a hard-coded
     * string there. Left here in case global code wants to get a refresh token
     * but one hopes it knows anyway!
     */
            $this->checkParm('authTypeSetting', $optionsB, $this->authTypeSetting);
    /**
     * hostedDomain is Google only
     */
            $this->checkParm('hostedDomain', $optionsB, $this->hostedDomain);
            $this->checkParm('serviceAccountName', $optionsB, $this->serviceAccountName);
            $this->checkParm('projectID', $optionsB, $this->projectID);
            $this->checkParm('impersonate', $optionsB, $this->impersonate);
            $this->checkParm('gmailXoauth2Credentials', $optionsB, $this-> gmailXoauth2Credentials);
            $this->checkParm('writeGmailCredentialsFile', $optionsB, $this-> writeGmailCredentialsFile);

            $this->refresh = false;
            $this->checkParm('refreshToken', $optionsB, $this->refreshToken);
            $this->checkParm('grantType', $optionsB, $this->grantType);

    /**
     *  these next two checks are needed because if a global code call does not
     *  specify values, they will be set to "" above rather than the defaults.
     */
            if ($this->gmailXoauth2Credentials == "") {
                $this->gmailXoauth2Credentials = self::GMAIL_XOAUTH2_CREDENTIALS;
            }

            if ($this->writeGmailCredentialsFile == "") {
                $this->writeGmailCredentialsFile = self::WRITE_GMAIL_CREDENTIALS_FILE;
            }

    /**
     * PHPMailer instance from calling global code or wrapper SendOauth2A
     */
            $this->checkParm('mail', $optionsB, $this->mail);

    /**
     * mailAuthSet and mailSMTPAddress will only be used by global code when it
     * also uses the interchange file. But if used this way, then SMTPAddressDefault
     * should be specified in the interchange file and will then
     * set mailSMTPAddress automatically unless overriden by global code
     */
            $this->checkParm('mailAuthSet', $optionsB, $this->mailAuthSet);
            $this->checkParm('mailSMTPAddress', $optionsB, $this->mailSMTPAddress);
            $this->checkParm('SMTPAddressDefault', $optionsB, $this->SMTPAddressDefault);
            $this->checkParm('fromNameDefault', $optionsB, $this->fromNameDefault);

    /**
     * only for Basic authentication (LOGIN or PLAIN)
     */
            $this->checkParm('SMTPPassword', $optionsB, $this->SMTPPassword);
            $this->assignParms($optionsC2);

    /**
     * redirectURI is not needed for authentication, but GoogleAPI insists on
     * a valid format URI in .json - which (if GoogleAPI) we will create, so
     * exchange a null URI for a dummy one
     */
            if (empty($this->redirectURI)) {
                $this->redirectURI = "https://blah.com";
            }
        } else {
       

    /**
     * assume we were instantiated from decomplexity\SendOauth2 wrapper
     */
            $this->mailAuthSet = $optionsB['mailAuthSet'];
            $this->mailSMTPAddress = $optionsB['mailSMTPAddress'];
            $this->mail = $optionsB['mail'];
    /**
     * SMTPAddress (below) is used by getOauth64() (below) as username and by Basic Auth
     * and PHPMailer as Logon address
         */

            $optionsC = file_get_contents(self::OAUTH2_PARAMETERS_FILE . "_" . $this->mailAuthSet . ".txt");
    /**
     * If the contents of the file were encrypted by SendOauth2D, decrypt it here.
     * Just decrypt $optionsC
     */

            $optionsC1 = explode(self::IMPLODE_GLUE, $optionsC);

    /**
     * optionsC1 now contains the following parms that were originally saved by SendOauthD
     * note that implode and explode remove keys, so the order is important.
     * if any changes, cross-check with the list in SendAuth2D in method saveParameters
     */

            list(
            $this->tenant,
            $this->clientId,
            $this->clientSecret,
            $this->clientCertificatePrivateKey,
            $this->clientCertificateThumbprint,
            $this->redirectURI,
            $this->serviceProvider,
            $this->authTypeSetting,
            $this->fromNameDefault,
            $this->SMTPAddressDefault,
            $this->SMTPPassword,

            $this->hostedDomain,
            $this->serviceAccountName,
            $this->projectID,
            $this->impersonate,
            $this->gmailXoauth2Credentials,
            $this->writeGmailCredentialsFile,


            $this->refresh,
            $this->refreshToken,
            $this->grantType,
            ) = $optionsC1;

    /**
     * to avoid using positional parameters, e.g. [0] for ease of maintenance use indexed parameters instead...
     * and for details of 'refresh', see SendOauthC;
     */

            $this->assignParms($optionsC2);

    /**
            $optionsC2 = [
            'tenant' => $this->tenant,
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'clientCertificatePrivateKey' => $this->clientCertificatePrivateKey,
            'clientCertificateThumbprint' => $this->clientCertificateThumbprint,
            'redirectURI' => $this->redirectURI,
            'serviceProvider' => $this->serviceProvider,
            'authTypeSetting' => $this->authTypeSetting,
            'hostedDomain' => $this->hostedDomain,
            'serviceAccountName => $this->serviceAccountName,
            'projectID' => $this->projectID,
            'impersonate' =>  $this->impersonate,
            'gmailXoauth2Credentials' =>  $this-> gmailXoauth2Credentials,
            'writeGmailCredentialsFile' =>  $this-> writeGmailCredentialsFile,
            'refresh' => false,
            'grantType' => $this->grantType,
            'mailSMTPAddress' => $this-> mailSMTPAddress,

    /**
     *
     * using  'accessType' = 'offline' and 'accessPrompt' = 'none'
     * should not be needed unless the refresh token is one that can expire
     */
        }  // ends number of args <= NUMPARMS

    /**
     * check if service provider is GoogleAPI; if so, write credentials .json
     * Must be written before instantiating SendOauth2C because  SendOauth2C
     * issues a setAuthConfig that uses the credentials .json
     */

        $this->GoogleAPIOauth2File();

    /**
     * mailSMTPAddress should normally NOT be set in global but allowed to take the SendOauth2 default,
     * unless pro tem you want to override the SendOauth2 default
     * Note that neither MSFT nor Google will will allow sending from 'arbitrary' addresses
     * NB: PHPMailer mail->Username is an SMTP ADDRESS
     */

        if (empty($this->mailSMTPAddress)) {
            $this->mailSMTPAddress = $this->SMTPAddressDefault;
        }

        $this->mail->Username = $this->mailSMTPAddress; // SMTP sender email address (MSFT or Google email account)


    /**
     * then instantiate C (and hence the provider)
     */
        $this->Send_Oauth_C_obj = new SendOauth2C($optionsC2);
        $this->scope = $this->Send_Oauth_C_obj->getScope();
        $this->provider = $this->getProvider();
        $this->mail->Host = $this->getSMTPserver();
        $this->mail->AuthType = $this->authTypeSetting;        // usually XOAUTH2 or LOGIN



    /**
     * now Oauth2 authenticate, but only if XOAUTH2 !
     */

        if ($this->authTypeSetting == "XOAUTH2") {
            $this->mail->refresh_token = $this->refreshToken;
            $this->provider = $this->getProvider();
        } else {
    /**
     * Give PHPMailer the SMTP password only if AUTHN is either CRAM-MD5, LOGIN or PLAIN
     * Google's PLAIN-CLIENTTOKEN OAUTHBEARER and XOAUTH[1] are unsupported
     */
            $this->mail->Password = $this->SMTPPassword;
        }

    /**
     * ends __construct
     */
    }

    /**
     * getProvider retrieves the provider-package object (Azure, Google,...) from SendOauthC
     */

    public function getProvider()
    {
        return $this->Send_Oauth_C_obj->setProvider();

    /**
     * ends getProvider method
     */
    }


    /**
     * getSMTPserver retrieves the server domain name from SendOauthC
     * the server domain name cannot be assigned to $this->mail->Host there
     * as SendOauthC is instantiated both from here AND (optionally) from SendOauthD
     * and SendOauthD is independent of PHPMailer and hence has no $mail object
     */

    public function getSMTPserver()
    {
        return $this->Send_Oauth_C_obj->setSMTPServer();

    /**
     * ends getsetSMTPServermethod
     */
    }


    /**
     * @return returns parameters to SendOauth2A
     */
    public function getOauth2Settings()
    {
        return [
        'fromNameDefault' => $this->fromNameDefault,
        'mailSMTPAddress' => $this->mailSMTPAddress,
        'authTypeSetting' => $this->authTypeSetting
        ];

    /**
     * ends getOauth2Settings method
     */
    }


    /**
     * takes an updated refresh token from Theleague's league/oauth2-client/src/Token/AccessToken.php
     * and rewrites the OAUTH2_PARAMETERS_FILE as originally read (above) but
     * now containing the new refresh token
     * if the League\\OAuth2\\Client\\Token\AccessToken one-line change is not implemented,
     * it will be detected (below) and the existing refresh token not modified
     */
    public function storeNewRefreshToken()
    {
    /**
     * Since this is only called from SendOauth2A and not global code, there
     * is no need to check if the refresh token is set manually in global code
     * and hence cannot be renenewed here.
     * Now check if key exists - use a focused key to avoid any clash in
     * TheLeague or related code.
     */
        $key = "League\\OAuth2\\Client\\Token" . "\\updatedRefreshToken";
        if (array_key_exists($key, $_SESSION)) {
            $nrt = $_SESSION[$key];


    /**
     * check if value was not empty
     */
            if (!empty($nrt)) {
                $optionsD1 = [
                $this->tenant,
                $this->clientId,
                $this->clientSecret,
                $this->clientCertificatePrivateKey,
                $this->clientCertificateThumbprint,
                $this->redirectURI,
                $this->serviceProvider,
                $this->authTypeSetting,
                $this->fromNameDefault,
                $this->SMTPAddressDefault,
                $this->SMTPPassword,
                $this->hostedDomain,
                $this->serviceAccountName,
                $this->projectID,
                $this->impersonate,
                $this->gmailXoauth2Credentials,
                $this->writeGmailCredentialsFile,
                $this->refresh,
                $nrt,
                $this->grantType,
                ];
    /**
     * Don't override $this->refreshToken as having the old and new may help any diagnostics
     */

                $optionsD2 = implode(self::IMPLODE_GLUE, $optionsD1);

    /**
     * If the contents of the file (below) need encrypting, do it here.
     * Just encrypt $optionsD2
     */

    /**
     * write the completed set of parameters to file
     */
                file_put_contents(
                    self::OAUTH2_PARAMETERS_FILE . "_" . $this->mailAuthSet . ".txt",
                    $optionsD2
                );
            } // ends 'if token was found but value was not empty'
        }    // ends 'if a token was actually found in  $_SESSION
    
    /**
     * ends storeNewRefreshToken method
     */
    }
   
    /**
     * checks / nullifies Constructor arguments when called from global
     * code (as opposed to from SendOauthA)
     */
    protected function checkParm($inparm, $options, &$outparm)
    {
        return (array_key_exists($inparm, $options) && isset($options[$inparm]))
                 ? $outparm = $options[$inparm]
                 : $outparm = '';
    }
   


    /**
     * this assignment is done in two places - when SendOauth2B called
     * from SendOauthA and from global code
     * So don't duplicate the code
     */

    protected function assignParms(&$outparm)
    {
        $outparm = [
            'tenant' => $this->tenant,
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'clientCertificatePrivateKey' => $this->clientCertificatePrivateKey,
            'clientCertificateThumbprint' => $this->clientCertificateThumbprint,
            'redirectURI' => $this->redirectURI,
            'serviceProvider' => $this->serviceProvider,
            'authTypeSetting' => $this->authTypeSetting,
            'hostedDomain' => $this->hostedDomain,
            'serviceAccountName' => $this->serviceAccountName,
            'projectID' => $this->projectID,
            'impersonate' =>  $this->impersonate,
            'gmailXoauth2Credentials' => $this-> gmailXoauth2Credentials,
            'writeGmailCredentialsFile' => $this-> writeGmailCredentialsFile,
            'refresh' => false,
            'grantType' => $this->grantType,
            'mailSMTPAddress' => $this-> mailSMTPAddress,
            ];
    }
 
    /**
     * what follows is PHPMailer's OAuth functionality, elaborated  to support
     * client_credentials grant (includign Google Service Accounts. If necessary,
     * the present class can be extended with a child class containing a new getToken()
     * to support other grant flows
     *
     * For client_credentials flow ('CCF'), the provider
     * (e.g. TheNetworg's oauth2-azure/src/Provider/Azure.php)
     * must be instantiated with a $tenant value if the provider itself calls the token endpoint
     * with a 'common' organisation parameter, because (at least) MSFT CCF MUST have
     * a specific tenant.
     *
     * grantType defaults to authorization_code to forestall a BC
     *
     */

    /**
     * Get a new RefreshToken.
     * @return RefreshToken
     */
    protected function getGrant()
    {
        return new RefreshToken();
    }


    /**
     * Get a new AccessToken.
     *
     * @return AccessToken
     */
    protected function getToken()
    {
    /**
     * default the grant to authorization_code
     */
        if (empty($this->grantType)) {
            $this->grantType = self::AUTHCODE;
        }

        switch ($this->grantType) {
            case self::AUTHCODE:
                if (!$this->Send_Oauth_C_obj->getIsGoogleAPI()) {
                        return $this->provider->getAccessToken(
                            $this->getGrant(),
                            ['refresh_token' => $this->refreshToken]
                        );
                } else {
    /**
     * Google API
     * Notes:
     * refreshToken() is a backward compatibility alias for fetchAccessTokenWithRefreshToken
     * but note that $this->refreshToken below is  a local property
     * For either, Google returns an ARRAY (not a string) of which the value of the
     * access_token key is the one needed by getOauth64() below.
     * $this->mail->Host is refreshed because Google nullifes it
     */
                    $this->provider->useApplicationDefaultCredentials();
                    $this->mail->Host = $this->getSMTPserver();
                    $token_array = $this->provider->fetchAccessTokenWithRefreshToken($this->refreshToken);
                    return $token_array['access_token'];
                }
                break;


            case self::CLIENTCRED:
                try {
                    $this->scopeError($this->scope);
                } catch (Exception $ex) {
                        echo ($ex->getMessage());
                        exit;
                }

    /**
     * Google API access tokens for service accounts are
     * obtained by presenting the credentials json
     */
                if ($this->Send_Oauth_C_obj->getIsGoogleAPI()) {
                    $token = $this->provider->fetchAccessTokenWithAssertion();

    /**
     * the token is an array with several fields. We just want the access token
     */
                    return $token['access_token'];
                } else {

    /**
     * provider other than Google API service account
     */
                    return $this->provider->getAccessToken(
                        $this->grantType,
                        ['scope' => $this->scope,
                        ]
                    );
                }
                break;


            default:
                try {
                    $this->grantError($this->grantType);
                } catch (Exception $ex) {
                    echo ($ex->getMessage());
                    exit;
                }
                break;
        }
    }


    protected function scopeError($scope)
    {
        if (empty($scope)) {
            throw new Exception(
                'ERROR in OAuth in SendOauth2B - ' .
                'no resource domain specified ' .
                'for client_credentials grant scope: <br />' .
                'Should be e.g. (for MSFT) https://outlook.office365.com/.default'
            );
        }
    }

    protected function grantError($grantType)
    {
        throw new Exception(
            'ERROR in OAuth in SendOauth2B - ' .
            'no (or invalid) grant type specified: ' .
            $this->grantType . '<br />' .
            'Should be ' . self::AUTHCODE . ' or ' . self::CLIENTCRED
        );
    }


    /**
     * Generate a base64-encoded OAuth token.
     *
     * @return string
     */


    public function getOauth64()
    {
    /**
     * Get a new token if it's not available or has expired
     */
        if (null === $this->accessToken || $this->accessToken->hasExpired()) {
            $this->accessToken = $this->getToken();
        }
        return base64_encode(
            'user=' .
            $this->mailSMTPAddress .
            "\001auth=Bearer " .
            $this->accessToken .
            "\001\001"
        );
    }

    /**
     * ends class SendOauth2B
     */
}
