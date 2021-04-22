<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Http;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use SlackPhp\Framework\{Application, Coerce};

class MultiTenantHttpServer extends HttpServer
{
    private const APP_ID_KEY = '_app';

    /** @var array<string, mixed> */
    private array $apps = [];
    private ?Closure $appIdDetector;

    /**
     * Register an app by app ID to be routed to.
     *
     * @param string $appId
     * @param string|callable(): Application $appFactory App class name, include file, or factory callback.
     * @return $this
     */
    public function registerApp(string $appId, $appFactory): self
    {
        $this->apps[$appId] = $appFactory;

        return $this;
    }

    /**
     * @param callable(ServerRequestInterface $request): ?string $appIdDetector
     * @return $this
     */
    public function withAppIdDetector(callable $appIdDetector): self
    {
        $this->appIdDetector = $appIdDetector instanceof Closure
            ? $appIdDetector
            : Closure::fromCallable($appIdDetector);

        return $this;
    }

    protected function getApp(): Application
    {
        // Get the app ID from the request.
        $appId = $this->getAppIdDetector()($this->getRequest());
        if ($appId === null) {
            throw new HttpException('Cannot determine app ID');
        }

        // Create the app for the app ID.
        $app = $this->instantiateApp($appId);

        // Reconcile the registered app ID with the App's configured ID.
        $configuredId = $app->getConfig()->getId();
        if ($configuredId === null) {
            $app->getConfig()->withId($appId);
        } elseif ($configuredId !== $appId) {
            throw new HttpException("ID mismatch for app ID: {$appId}");
        }

        // Set the App to the Server.
        $this->withApp($app);

        return parent::getApp();
    }

    /**
     * @param string $appId ID for the application
     * @return Application
     * @noinspection PhpIncludeInspection
     */
    private function instantiateApp(string $appId): Application
    {
        // Create the app from its configured factory, and make sure it's valid.
        $factory = $this->apps[$appId] ?? null;
        if (is_null($factory)) {
            throw new HttpException("No app registered for app ID: {$appId}");
        } elseif (is_string($factory) && class_exists($factory)) {
            $app = new $factory();
        } elseif (is_string($factory) && is_file($factory)) {
            $app = require $factory;
        } elseif (is_callable($factory)) {
            $app = $factory();
        } else {
            throw new HttpException("Invalid application for app ID: {$appId}");
        }

        return Coerce::application($app);
    }

    private function getAppIdDetector(): Closure
    {
        return $this->appIdDetector ?? function (ServerRequestInterface $request): ?string {
            return $request->getQueryParams()[self::APP_ID_KEY] ?? null;
        };
    }
}
