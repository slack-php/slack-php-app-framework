<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Listeners;

use Closure;
use SlackPhp\Framework\{Context, Exception, Listener};

use function is_string;
use function realpath;

/**
 * Listener that has its logic provided as a callback function.
 */
class Callback implements Listener
{
    private Closure $callback;

    /**
     * Creates a Callback Listener that includes a file and attempts to treat the returned value as a listener.
     *
     * @param string $path
     * @return self
     */
    public static function forInclude(string $path): self
    {
        return new self(function (Context $context) use ($path) {
            $realPath = realpath($path);
            if (!is_string($realPath)) {
                throw new Exception("Invalid listener path: {$path}");
            }

            /** @noinspection PhpIncludeInspection */
            $listener = require $realPath;
            if (!$listener instanceof Listener) {
                throw new Exception("No listener returned from path: {$path}");
            }

            $listener->handle($context);
        });
    }

    /**
     * @param callable(Context): void $callback Callback to be used as the Listener.
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback instanceof Closure ? $callback : Closure::fromCallable($callback);
    }

    public function handle(Context $context): void
    {
        ($this->callback)($context);
    }
}
