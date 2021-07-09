<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Listeners;

use SlackPhp\Framework\{Context, Listener};

/**
 * Base class for listener classes that may have both sync (pre-ack) and async (post-ack) logic.
 *
 * Note: Does not automatically defer. This allows flexibility to decide if deferring is needed. In many cases, the
 *       logic that happens prior to the ack represents a complete handling of the event. If additional logic must be
 *       performed after the ack, then $context->defer() should be called in the handleAck() method implementation.
 */
abstract class Base implements Listener
{
    public function handle(Context $context): void
    {
        if ($context->isAcknowledged()) {
            $this->handleAfterAck($context);
        } else {
            $this->handleAck($context);
        }
    }

    /**
     * Handles application logic that must be preformed prior to the "ack" and Slack's 3-second timeout.
     *
     * By default, this does an ack. You should override this method with your own implementation to do more.
     *
     * @param Context $context
     */
    protected function handleAck(Context $context): void
    {
        $context->ack();
    }

    /**
     * Handles application logic that can or must happen after the "ack" and is not subject to Slack's 3-second timeout.
     *
     * By default, this does nothing. You should override this method with your own implementation if you use defer().
     *
     * @param Context $context
     */
    protected function handleAfterAck(Context $context): void
    {
        // No-op.
    }
}
