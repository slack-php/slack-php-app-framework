<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Clients;

use SlackPhp\BlockKit\Surfaces\Message;

class SimpleRespondClient implements RespondClient
{
    use SendsHttpRequests;

    public function respond(string $responseUrl, Message $message): void
    {
        $this->sendJsonRequest('POST', $responseUrl, $message->toArray());
    }
}
