<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Listeners;

use SlackPhp\Framework\{Context, Listener};
use SlackPhp\Framework\Contexts\PayloadType;

/**
 * Simple listener that displays/logs a "Work in progress" message in whichever medium makes the most sense.
 */
class WIP implements Listener
{
    public function handle(Context $context): void
    {
        $message = 'Work in progress';
        if ($context->payload()->isType(PayloadType::command())) {
            $context->ack($message);
        } elseif ($context->payload()->isType(PayloadType::viewSubmission())) {
            $context->view()->push($message);
        } elseif ($context->payload()->get('trigger_id')) {
            $context->modals()->open($message);
        } elseif ($context->payload()->getResponseUrl()) {
            $context->respond($message);
        } else {
            $context->logger()->debug($message);
        }
    }
}
