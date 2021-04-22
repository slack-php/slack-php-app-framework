<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

use Psr\Log\{LoggerInterface, NullLogger};
use SlackPhp\Framework\Auth\{AppCredentials, AppCredentialsStore};

/**
 * An AppServer is a protocol-specific and/or framework-specific app runner.
 *
 * Its main responsibilities include:
 * 1. Receiving an incoming Slack request via the specific protocol/framework.
 * 2. Authentication, including incoming Slack requests or outgoing connections.
 * 3. Parsing the Slack request and payload into a Slack `Context`.
 * 4. Using the app to process the Slack Context.
 * 5. Providing a protocol-specific way for the app to "ack" back to Slack.
 * 6. Providing a protocol-specific way for the app to "defer" the processing of a Context until after the "ack".
 */
abstract class AppServer
{
    private ?Application $app;
    private ?AppCredentialsStore $appCredentialsStore;
    private ?LoggerInterface $logger;

    /**
     * Creates a new instance of the server for fluent configuration.
     *
     * @return static
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * Creates the server.
     *
     * Cannot override. If initialization logic is needed, override the `init()` method.
     */
    final public function __construct()
    {
        $this->init();
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
     * Gets the application being run by the Server
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
     * Sets the app credentials store for the Server.
     *
     * @param AppCredentialsStore $appCredentialsStore
     * @return $this
     */
    public function withAppCredentialsStore(AppCredentialsStore $appCredentialsStore): self
    {
        $this->appCredentialsStore = $appCredentialsStore;

        return $this;
    }

    /**
     * Gets the app credentials to use for authenticating the app being run by the Server.
     *
     * If app credentials are not provided in the AppConfig, the app credentials store will be used to fetch them.
     *
     * @return AppCredentials
     */
    protected function getAppCredentials(): AppCredentials
    {
        $config = $this->getApp()->getConfig();
        $credentials = $config->getAppCredentials();

        if (!$credentials->supportsAnyAuth() && isset($this->appCredentialsStore)) {
            $credentials = $this->appCredentialsStore->getAppCredentials($config->getId());
            $config->withAppCredentials($credentials);
        }

        return $credentials;
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
     * Gets the logger for the Server.
     *
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
     * Initializes a server. Called at the time of construction.
     *
     * Implementations MAY override.
     */
    protected function init(): void
    {
        // Do nothing by default.
    }

    /**
     * Starts receiving and processing requests from Slack.
     */
    abstract public function start(): void;

    /**
     * Stops receiving requests from Slack.
     *
     * Implementations MAY override.
     */
    public function stop(): void
    {
        // Do nothing by default.
    }
}
