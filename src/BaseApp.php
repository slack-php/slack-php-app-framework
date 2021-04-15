<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

/**
 * Base class for Applications created as a class, instead of using the fluent App façade interface.
 */
abstract class BaseApp extends Application
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
        parent::__construct($this->router);
        $this->prepareConfig($this->config);
        $this->prepareRouter($this->router);
    }

    /**
     * Prepares the application's router.
     *
     * Implementations MUST override this method to configure the Router.
     *
     * @param Router $router
     */
    abstract protected function prepareRouter(Router $router): void;

    /**
     * Prepares the application's config.
     *
     * Implementations SHOULD override this method to configure the Application.
     *
     * @param AppConfig $config
     */
    protected function prepareConfig(AppConfig $config): void
    {
        // Does nothing by default.
    }
}
