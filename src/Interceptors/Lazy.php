<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Interceptors;

use Closure;
use SlackPhp\Framework\{Context, Interceptor, Listener};

/**
 * Lazily creates an interceptor at the time that it needs to be executed.
 */
class Lazy implements Interceptor
{
    private Closure $callback;

    /**
     * @param callable(): Interceptor $callback Interceptor factory callback.
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback instanceof Closure ? $callback : Closure::fromCallable($callback);
    }

    public function intercept(Context $context, Listener $listener): void
    {
        /** @var Interceptor $interceptor */
        $interceptor = ($this->callback)();
        $interceptor->intercept($context, $listener);
    }
}
