<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Auth;

use SlackPhp\Framework\Exception;

/**
 * @TODO I have not considered the user token case yet. This may need changes later.
 */
interface TokenStore
{
    /**
     * @param string|null $teamId
     * @param string|null $enterpriseId
     * @return string
     * @throws Exception if bot token is not available
     */
    public function get(?string $teamId, ?string $enterpriseId): ?string;

    /**
     * @param string|null $teamId
     * @param string|null $enterpriseId
     * @param string $token
     */
    public function set(?string $teamId, ?string $enterpriseId, string $token): void;
}
