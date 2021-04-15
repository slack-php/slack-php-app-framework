<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Interceptors;

use Closure;
use SlackPhp\Framework\{Context, Interceptor, Listener};

/**
 * Interceptor that lets you tap into the context before the listener is executed.
 */
class Tap implements Interceptor
{
    private Closure $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback instanceof Closure ? $callback : Closure::fromCallable($callback);
    }

    public function intercept(Context $context, Listener $listener): void
    {
        ($this->callback)($context);
        $listener->handle($context);
    }
}
