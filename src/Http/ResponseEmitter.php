<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Emits an HTTP response
 */
interface ResponseEmitter
{
    /**
     * @param ResponseInterface $response
     * @throws HttpException if response cannot be emitted.
     */
    public function emit(ResponseInterface $response): void;
}
