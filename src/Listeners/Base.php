<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Listeners;

use SlackPhp\Framework\{Context, Listener};

/**
 * Base class for listener classes that may have both sync (pre-"ack") and async (post-"ack") logic.
 *
 * By default, the context is set to defer, so that post-ack logic will be executed. In some cases, a complete handling
 * of an event can be done prior to the ack. In that case, the `handleAck()` method can call `$content->defer(false);`.
 */
abstract class Base implements Listener
{
    public function handle(Context $context): void
    {
        // Handle async logic, if executed post-ack.
        if ($context->isAcknowledged()) {
            $this->handleAfterAck($context);
            return;
        }

        // Handle sync logic, if executed pre-ack.
        $context->defer(true);
        $this->handleAck($context);
        if (!$context->isAcknowledged()) {
            $context->ack();
        }
    }

    /**
     * Handles application logic that must be preformed prior to the "ack" and Slack's 3-second timeout.
     *
     * By default, this does nothing. You should override this method with your own implementation.
     *
     * @param Context $context
     */
    protected function handleAck(Context $context): void
    {
        // No-op. Override as needed.
    }

    /**
     * Handles application logic that can or must happen after the "ack" and is not subject to Slack's 3-second timeout.
     *
     * By default, this does nothing. You should override this method with your own implementation.
     *
     * @param Context $context
     */
    protected function handleAfterAck(Context $context): void
    {
        // No-op. Override as needed.
    }
}
