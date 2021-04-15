<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Clients;

use Jeremeamia\Slack\BlockKit\Surfaces\Message;
use SlackPhp\Framework\Exception;

interface RespondClient
{
    /**
     * Responds to a Slack message using a response_url.
     *
     * @param string $responseUrl URL used to respond to Slack message
     * @param Message $message Message to respond with
     * @throws Exception if responding was not successful
     */
    public function respond(string $responseUrl, Message $message): void;
}
