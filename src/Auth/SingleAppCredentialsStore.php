<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Auth;

use SlackPhp\Framework\{Env, Exception};

class SingleAppCredentialsStore implements AppCredentialsStore
{
    private AppCredentials $appCredentials;

    /**
     * @param string|null $signingKey
     * @param string|null $defaultBotToken
     * @param string|null $clientId
     * @param string|null $clientSecret
     */
    public function __construct(
        ?string $signingKey = null,
        ?string $defaultBotToken = null,
        ?string $clientId = null,
        ?string $clientSecret = null
    ) {
        $env = Env::vars();
        $signingKey ??= $env->getSigningKey();
        if ($signingKey === null) {
            throw new Exception('Signing key not set for App');
        }

        $this->appCredentials = new AppCredentials(
            $signingKey,
            $defaultBotToken ?? $env->getBotToken(),
            $clientId ?? $env->getClientId(),
            $clientSecret ?? $env->getClientSecret(),
        );
    }

    /**
     * @param string $appId
     * @return AppCredentials
     * @throws Exception if bot app credentials cannot be retrieved
     */
    public function getAppCredentials(string $appId): AppCredentials
    {
        return $this->appCredentials;
    }
}
