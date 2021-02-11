<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Http;

use Closure;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use SlackPhp\Framework\Application;
use Throwable;

class MultiTenantHttpServer extends HttpServer
{
    private const APP_ID_KEY = '_app';

    /** @var array<string, callable> */
    private array $apps = [];
    private ?Closure $appIdDetector;

    public function registerApp(string $appId, callable $appFactory): self
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

    /**
     * Starts receiving and processing requests from Slack.
     */
    public function start(): void
    {
        try {
            parent::start();
        } catch (Throwable $exception) {
            $response = new Response($exception->getCode() ?: 500);
            $this->getLogger()->error('Error responding to incoming Slack request', compact('exception'));
            $this->emitResponse($response);
        }
    }

    protected function getApp(): Application
    {
        // Get the app ID from the request.
        $appId = $this->getAppIdDetector()($this->getRequest());
        if ($appId === null) {
            throw new HttpException('Cannot determine app ID');
        }

        // Make sure an app was registered for the app ID.
        $appFactory = $this->apps[$appId] ?? null;
        if ($appFactory === null) {
            throw new HttpException("No app registered for app ID: {$appId}");
        }

        // Create the app from its configured factory, and make sure it's valid.
        $app = $appFactory();
        if (!$app instanceof Application) {
            throw new HttpException("Invalid application for app ID: {$appId}");
        }

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

    private function getAppIdDetector(): Closure
    {
        return $this->appIdDetector ?? function (ServerRequestInterface $request): ?string {
            return $request->getQueryParams()[self::APP_ID_KEY] ?? null;
        };
    }
}
