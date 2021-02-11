<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

/**
 * A Slack application for reacting to Slack events, user-initiated commands, or other user interactions with Slack.
 *
 * Slack applications handle incoming Slack requests/events using Listener(s) (usually via a Router).
 */
class Application implements Listener
{
    protected Listener $listener;
    protected AppConfig $config;

    public function __construct(?Listener $listener = null, ?AppConfig $config = null)
    {
        $this->listener = $listener ?? new Listeners\Ack();
        $this->config = $config ?? new AppConfig();
    }

    public function getConfig(): AppConfig
    {
        return $this->config;
    }

    public function handle(Context $context): void
    {
        $context->withAppConfig($this->config);
        $this->listener->handle($context);
        if (!$context->isAcknowledged()) {
            $context->ack();
        }
    }

    public function run(?AppServer $server = null): void
    {
        // Default to the basic HTTP server which gets data from superglobals.
        $server ??= new Http\HttpServer();

        // Start the server to run the app.
        $server->withApp($this)->start();
    }
}
