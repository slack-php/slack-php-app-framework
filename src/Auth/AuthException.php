<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Auth;

use SlackPhp\Framework\Exception;
use Throwable;

class AuthException extends Exception
{
    public function __construct(string $message, int $statusCode = 401, Throwable $previous = null)
    {
        parent::__construct("Auth Failed: {$message}", $statusCode, $previous);
    }
}
