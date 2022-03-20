<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Clients;

use SlackPhp\Framework\Exception;

use function array_filter;

class OAuthClient
{
    private ApiClient $apiClient;

    public function __construct(?ApiClient $apiClient = null)
    {
        $this->apiClient = $apiClient ?? new SimpleApiClient(null);
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $temporaryAccessCode
     * @param string|null $redirectUri
     * @return mixed[] Includes access_token, team.id, and enterprise.id fields
     * @throws Exception
     */
    public function createAccessToken(
        string $clientId,
        string $clientSecret,
        string $temporaryAccessCode,
        ?string $redirectUri = null
    ): array {
        return $this->apiClient->call('oauth.v2.access', array_filter([
            'code' => $temporaryAccessCode,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ]));
    }

    /**
     * @param string $accessToken
     * @param bool|null $test
     * @return mixed[]
     * @throws Exception
     */
    public function revokeAccessToken(string $accessToken, ?bool $test = null): array
    {
        return $this->apiClient->call('auth.revoke', [
            'token' => $accessToken,
            'test' => (int) $test,
        ]);
    }
}
