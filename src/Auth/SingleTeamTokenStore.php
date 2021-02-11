<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Auth;

use SlackPhp\Framework\{Env, Exception};

class SingleTeamTokenStore implements TokenStore
{
    private ?string $token;

    public function __construct(?string $token = null)
    {
        $this->token = $token ?? Env::vars()->getBotToken();
    }

    public function get(?string $teamId, ?string $enterpriseId): string
    {
        if ($this->token === null) {
            throw new Exception('No bot token available: Bot token is null or is missing from environment');
        }

        return $this->token;
    }

    public function set(?string $teamId, ?string $enterpriseId, string $token): void
    {
        throw new Exception('Cannot change bot token in SingleTokenStore');
    }
}
