<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Listeners;

use SlackPhp\BlockKit\Surfaces\Message;
use SlackPhp\Framework\{Coerce, Context, Listener};

/**
 * Simple listener that merely acks.
 *
 * Allows for a provided message to be included in the ack, which is especially useful for commands.
 */
class Ack implements Listener
{
    private ?Message $message;

    /**
     * @param Message|array|string|null $message Message to include in ack (for commands).
     */
    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = Coerce::message($message);
        }
    }

    public function handle(Context $context): void
    {
        $context->ack($this->message);
    }
}
