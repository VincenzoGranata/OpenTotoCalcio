<?php

namespace Modules\Emails\OAuth2;

use League\OAuth2\Client\Provider\GenericProvider;

class KeycloakLogin extends GenericProvider implements ProviderInterface
{
    protected static $options = [];
    private $customScopes = [];

    public function __construct(array $options = [], array $collaborators = [])
    {
        $authServerUrl = $options['auth_server_url'];
        $realm = $options['realm'];
        $publicUrl = $options['public_auth_server_url'] ?? $authServerUrl;

        $this->customScopes = ['openid', 'profile', 'email'];

        $config = array_merge($options, [
            'urlAuthorize' => $publicUrl.'/realms/'.$realm.'/protocol/openid-connect/auth',
            'urlAccessToken' => $authServerUrl.'/realms/'.$realm.'/protocol/openid-connect/token',
            'urlResourceOwnerDetails' => $authServerUrl.'/realms/'.$realm.'/protocol/openid-connect/userinfo',
            'redirectUri' => base_url().'/oauth2_login.php',
        ]);

        parent::__construct($config, $collaborators);
    }

    public function getDefaultScopes()
    {
        return $this->customScopes;
    }

    public function getScopeSeparator()
    {
        return ' ';
    }

    public function getOptions()
    {
        return self::$options;
    }

    public static function getConfigInputs()
    {
        return [
            'auth_server_url' => [
                'label' => 'Auth Server URL (internal)',
                'type' => 'text',
            ],
            'public_auth_server_url' => [
                'label' => 'Public Auth Server URL (browser)',
                'type' => 'text',
            ],
            'realm' => [
                'label' => 'Realm',
                'type' => 'text',
            ]
        ];
    }

    public function getUser($access_token)
    {
        $response = $this->getAuthenticatedRequest(
            'GET',
            $this->getResourceOwnerDetailsUrl($access_token),
            $access_token
        );

        $user = $this->getParsedResponse($response);

        return $user['email'] ?? null;
    }
}
