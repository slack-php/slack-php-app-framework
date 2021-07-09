<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Listeners;

use SlackPhp\Framework\{Context, Listener};

/**
 * Base class for listeners that have both sync and async logic.
 *
 * Note: Does not automatically call defer. This allows the flexibility to not defer if the sync logic represents a
 *       complete handling of the request in some cases.
 *
 * @deprecated Use \SlackPhp\Framework\Listeners\Base instead.
 */
abstract class Dual implements Listener
{
    public function handle(Context $context): void
    {
        if ($context->isAcknowledged()) {
            $this->handleAfterAck($context);
        } else {
            $this->handleAck($context);
        }
    }

    abstract protected function handleAck(Context $context): void;

    abstract protected function handleAfterAck(Context $context): void;
}
