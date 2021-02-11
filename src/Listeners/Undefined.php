<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Listeners;

use SlackPhp\Framework\{Context, Listener};

class Undefined implements Listener
{
    public function handle(Context $context): void
    {
        $context->logger()->error('No listener matching payload');
    }
}
