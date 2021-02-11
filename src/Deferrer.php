<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

/**
 * Defers additional processing on a context until after the initial "ack", in order to avoid Slack's 3-second timeout.
 */
interface Deferrer
{
    /**
     * Defers additional processing on an "ack"ed context, usually via an async processing or queuing mechanism.
     *
     * This additional processing is typically asynchronous and happens "out of band" from the original Slack request.
     *
     * @param Context $context
     */
    public function defer(Context $context): void;
}
