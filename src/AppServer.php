<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

use Psr\Log\{LoggerInterface, NullLogger};

/**
 * An AppServer is a protocol-specific and/or framework-specific app runner.
 *
 * Its main responsibilities include:
 * 1. Receiving an incoming Slack request via the specific protocol/framework.
 * 2. Authenticating the Slack request.
 * 3. Parsing the Slack request and payload into a Slack `Context`.
 * 4. Using the app to process the Slack Context.
 * 5. Providing a protocol-specific way for the app to "ack" back to Slack.
 * 6. Providing a protocol-specific way for the app to "defer" the processing of a Context until after the "ack".
 */
abstract class AppServer
{
    private ?Application $app;
    private ?LoggerInterface $logger;

    /**
     * @return static
     */
    public static function new(): self
    {
        return new static();
    }

    final public function __construct()
    {
        // Do nothing. App and Logger are initialized lazily.
    }

    /**
     * @param Application|Listener|callable(Context): void|class-string $app
     * @return $this
     */
    public function withApp($app): self
    {
        $this->app = Coerce::application($app);

        // If the Server has no logger, use the application's logger.
        if (!isset($this->logger)) {
            $this->logger = $this->app->getConfig()->getLogger()->unwrap();
        }

        return $this;
    }

    /**
     * Gets the logger for the Server
     *
     * @return Application
     */
    protected function getApp(): Application
    {
        if (!isset($this->app)) {
            $this->withApp(new Application());
        }

        // If a logger for the Server is configured, use it as the app's logger.
        if (isset($this->logger)) {
            $this->app->getConfig()->getLogger()->withInternalLogger($this->logger);
        }

        return $this->app;
    }

    /**
     * Sets the logger for the Server.
     *
     * @param LoggerInterface $logger
     * @return $this
     */
    public function withLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        $this->logger ??= new NullLogger();

        return isset($this->app)
            ? $this->app->getConfig()->getLogger()
            : $this->logger;
    }

    /**
     * Starts receiving and processing requests from Slack.
     */
    abstract public function start(): void;

    /**
     * Stops receiving requests from Slack.
     *
     * Depending on the implementation, `stop()` may not need to actually do anything.
     */
    abstract public function stop(): void;
}
