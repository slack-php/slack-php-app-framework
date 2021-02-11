<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Auth;

interface AppCredentialsStore
{
    /**
     * @param string $appId
     * @return AppCredentials
     * @throws AuthException if bot app credentials cannot be retrieved
     */
    public function getAppCredentials(string $appId): AppCredentials;
}
