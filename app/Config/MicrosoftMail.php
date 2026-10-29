<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class MicrosoftMail extends BaseConfig
{
    public string $tenantId;
    public string $clientId;
    public string $clientSecret;
    public string $redirectUri;
    public string $senderEmail;

    public string $scopes =
        'offline_access '
        . 'https://graph.microsoft.com/User.Read '
        . 'https://graph.microsoft.com/Mail.Send';

    public function __construct()
    {
        parent::__construct();

        $this->tenantId = trim(
            (string) env('microsoft.tenantId', '')
        );

        $this->clientId = trim(
            (string) env('microsoft.clientId', '')
        );

        $this->clientSecret = (string) env(
            'microsoft.clientSecret',
            ''
        );

        $this->redirectUri = trim(
            (string) env('microsoft.redirectUri', '')
        );

        $this->senderEmail = strtolower(trim(
            (string) env('microsoft.senderEmail', '')
        ));
    }
}