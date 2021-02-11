<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

/**
 * A synchronous implementation of Deferrer, that does the additional processing prior to the "ack" HTTP response.
 *
 * Since async processing in PHP generally requires additional infrastructure or services, this implementation avoids
 * that by doing the additional processing immediately (before the "ack" HTTP response is actually sent to Slack). This
 * works as great default implementation for the HttpServer, but is still subject to Slack's 3-second timeout. If more
 * than 3 seconds is needed to process a Slack request, then a different deferrer implementation is needed.
 */
class PreAckDeferrer implements Deferrer
{
    private Listener $listener;

    /**
     * @param Listener $listener
     */
    public function __construct(Listener $listener)
    {
        $this->listener = $listener;
    }

    public function defer(Context $context): void
    {
        // Run the Slack context through the app/listener again, but this time with `isAcknowledged` set to `true`.
        $context->logger()->debug('Handling deferred processing before the ack response (synchronously)');
        $this->listener->handle($context);
    }
}
