# Kirby OAuth2 Email

[![Kirby 4](https://img.shields.io/badge/kirby-4-fb654f.svg)](https://getkirby.com)
[![Release](https://img.shields.io/github/v/release/nerdcel/kirby-oauth2-email)](https://github.com/nerdcel/kirby-oauth2-email/releases)
[![License](https://img.shields.io/github/license/nerdcel/kirby-oauth2-email)](https://github.com/nerdcel/kirby-oauth2-email/blob/main/LICENSE)

This plugin is a Kirby extension that allows you to authenticate users via OAuth2 and their email address.

This plugin implements the OAuth2 flow for the following providers:
- Google
- Microsoft
- Yahoo
- Azure

## Installation

```bash
composer require nerdcel/kirby-oauth2-email
```

Or download the [latest release](https://github.com/nerdcel/kirby-oauth2-email/releases) unzip it, copy it to `site/plugins/kirby-oauth2-email`.

## Setup your OAuth2 provider

You need to configure your OAuth2 provider to allow the authentication of your users.
You need to create an OAuth2 application and get the client ID and client secret. At this point, I assume you have a basic understanding of OAuth2 and how to setup your provider.

You most likely need to provide a redirect URL, which is `https://your-site.tld/email/oauth2`.
If this step is done, you can configure the plugin using the refresh token, client ID, and client secret (tenantId e.g. Azure).
Once this is done you can configure the plugin.

## Usage

Edit your `config.php` and add the following lines:

```php
'nerdcel.kirby-oauth2-email' => [
    'enabled' => true, // default: false
    'email' => 'example@your-provider.tdl',
    'service' => 'azure', // google, microsoft, yahoo, azure
    'client-id' => 'your-client-id',
    'client-secret' => 'your-client-secret',
    'tenant-id' => 'your-tenant-id', // only for azure
    'refresh-token' => 'your-refresh-token', // received from the first login
]
```

After you have configured the plugin, you should be able to send emails for example using the dreamform plugin or when you use the 2FA option for the panel.

## Usefull links

-   [PHPMailer Wiki](https://github.com/PHPMailer/PHPMailer/wiki/)
-   [Mailtrap Blog](https://mailtrap.io/blog/phpmailer-office-365/#Send-email-using-Outlook-SMTP)
