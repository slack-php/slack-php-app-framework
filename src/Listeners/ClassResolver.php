<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Listeners;

use SlackPhp\Framework\{Context, Exception, Listener};
use Throwable;

class ClassResolver implements Listener
{
    private string $class;

    /**
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function handle(Context $context): void
    {
        try {
            $listener = $context->container()->get($this->class);
        } catch (Throwable $ex) {
            throw new Exception('Could not resolve class name to Listener', 0, $ex);
        }

        if (!$listener instanceof Listener) {
            throw new Exception('Resolved class name to a non-Listener');
        }

        $listener->handle($context);
    }
}
