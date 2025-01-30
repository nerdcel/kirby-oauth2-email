<?php

namespace Nerdcel\OAuth2Email;

use Closure;
use Kirby\Email\Email;
use Kirby\Exception\InvalidArgumentException;
use League\OAuth2\Client\Provider\AbstractProvider;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;

//@see https://github.com/thephpleague/oauth2-google
use Kirby\Cms\App;
use League\OAuth2\Client\Provider\Google;

//@see https://packagist.org/packages/hayageek/oauth2-yahoo
use Hayageek\OAuth2\Client\Provider\Yahoo;

//@see https://github.com/stevenmaguire/oauth2-microsoft
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;

//@see https://github.com/greew/oauth2-azure-provider
use Greew\OAuth2\Client\Provider\Azure;

class OAuth2EMail extends Email
{
    /**
     * Sends email via PHPMailer library
     *
     * @throws InvalidArgumentException|Exception
     */
    public function send(bool $debug = false): bool
    {
        $mailer = new PHPMailer(true);

        // set sender's address
        $mailer->setFrom($this->from(), $this->fromName() ?? '');

        // optional reply-to address
        if ($replyTo = $this->replyTo()) {
            $mailer->addReplyTo($replyTo, $this->replyToName() ?? '');
        }

        // add (multiple) recipient, CC & BCC addresses
        foreach ($this->to() as $email => $name) {
            $mailer->addAddress($email, $name ?? '');
        }
        foreach ($this->cc() as $email => $name) {
            $mailer->addCC($email, $name ?? '');
        }
        foreach ($this->bcc() as $email => $name) {
            $mailer->addBCC($email, $name ?? '');
        }

        $mailer->Subject = $this->subject();
        $mailer->CharSet = 'UTF-8';

        // set body according to html/text
        if ($this->isHtml()) {
            $mailer->isHTML(true);
            $mailer->Body = $this->body()->html();
            $mailer->AltBody = $this->body()->text();
        } else {
            $mailer->Body = $this->body()->text();
        }

        // add attachments
        foreach ($this->attachments() as $attachment) {
            $mailer->addAttachment($attachment);
        }

        // Decide which service to use
        $service = option('nerdcel.kirby-oauth2-email.service');

        return match ($service) {
            'google' => $this->useGoogle($mailer, $debug),
            'yahoo' => $this->useYahoo($mailer, $debug),
            'microsoft' => $this->useMicrosoft($mailer, $debug),
            // Azure is the default service
            default => $this->useAzure($mailer, $debug),
        };
    }

    /**
     * @param  PHPMailer  $mailer
     * @param  bool  $debug
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    private function useAzure(PHPMailer $mailer, bool $debug = false): bool
    {
        // Configure PHPMailer for SMTP
        $mailer->isSMTP();
        $mailer->Host = 'smtp.office365.com';
        $mailer->SMTPAuth = true;
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = 587;

        // Set OAuth2 token
        $mailer->AuthType = 'XOAUTH2';

        //Create a new OAuth2 provider instance
        $provider = new Azure(
            [
                'clientId' => option('nerdcel.kirby-oauth2-email.client-id'),
                'tenantId' => option('nerdcel.kirby-oauth2-email.tenant-id'),
                'clientSecret' => option('nerdcel.kirby-oauth2-email.client-secret'),
            ]
        );

        //Pass the OAuth provider instance to PHPMailer
        $mailer->setOAuth(
            new OAuth(
                [
                    'provider' => $provider,
                    'clientId' => option('nerdcel.kirby-oauth2-email.client-id'),
                    'clientSecret' => option('nerdcel.kirby-oauth2-email.client-secret'),
                    'refreshToken' => option('nerdcel.kirby-oauth2-email.refresh-token'),
                    'userName' => option('nerdcel.kirby-oauth2-email.email'),
                ]
            )
        );

        return $this->transportIt($mailer, $debug);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function useGoogle(PHPMailer $mailer, bool $debug = false): bool
    {
        // Configure PHPMailer for SMTP
        $mailer->isSMTP();
        $mailer->Host = 'smtp.gmail.com';
        $mailer->SMTPAuth = true;
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = 587;

        // Set OAuth2 token
        $mailer->AuthType = 'XOAUTH2';

        $provider = new Google([
            'clientId' => option('nerdcel.kirby-oauth2-email.client-id'),
            'clientSecret' => option('nerdcel.kirby-oauth2-email.client-secret'),
        ]);

        $mailer = $this->setOAuth($provider, $mailer);

        return $this->transportIt($mailer, $debug);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function useMicrosoft(PHPMailer $mailer, bool $debug = false): bool
    {
        // Configure PHPMailer for SMTP
        $mailer->isSMTP();
        $mailer->Host = 'smtp.office365.com';
        $mailer->SMTPAuth = true;
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = 587;

        // Set OAuth2 token
        $mailer->AuthType = 'XOAUTH2';

        $provider = new Microsoft([
            'clientId' => option('nerdcel.kirby-oauth2-email.client-id'),
            'clientSecret' => option('nerdcel.kirby-oauth2-email.client-secret'),
        ]);

        $mailer = $this->setOAuth($provider, $mailer);

        return $this->transportIt($mailer, $debug);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function useYahoo(PHPMailer $mailer, bool $debug = false): bool
    {
        // Configure PHPMailer for SMTP
        $mailer->isSMTP();
        $mailer->Host = 'smtp.mail.yahoo.com';
        $mailer->SMTPAuth = true;
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = 587;

        // Set OAuth2 token
        $mailer->AuthType = 'XOAUTH2';

        $provider = new Yahoo([
            'clientId' => option('nerdcel.kirby-oauth2-email.client-id'),
            'clientSecret' => option('nerdcel.kirby-oauth2-email.client-secret'),
        ]);

        $mailer = $this->setOAuth($provider, $mailer);

        return $this->transportIt($mailer, $debug);
    }

    /**
     * @param  AbstractProvider  $provider
     * @param  PHPMailer  $mailer
     *
     * @return PHPMailer
     */
    private function setOAuth(AbstractProvider $provider, PHPMailer $mailer): PHPMailer
    {
        $mailer->setOAuth(
            new OAuth(
                [
                    'provider' => $provider,
                    'clientId' => option('nerdcel.kirby-oauth2-email.client-id'),
                    'clientSecret' => option('nerdcel.kirby-oauth2-email.client-secret'),
                    'refreshToken' => option('nerdcel.kirby-oauth2-email.refresh-token'),
                    'userName' => option('nerdcel.kirby-oauth2-email.email'),
                ]
            )
        );

        return $mailer;
    }

    private function transportIt(PHPMailer $mailer, $debug = false)
    {
        // accessible phpMailer instance
        $beforeSend = $this->beforeSend();

        if ($beforeSend instanceof Closure) {
            $mailer = $beforeSend->call($this, $mailer) ?? $mailer;

            if ($mailer instanceof PHPMailer === false) {
                throw new InvalidArgumentException('"beforeSend" option return should be instance of PHPMailer\PHPMailer\PHPMailer class');
            }
        }

        if ($debug === true) {
            return $this->isSent = true;
        }

        return $this->isSent = $mailer->send();
    }
}
