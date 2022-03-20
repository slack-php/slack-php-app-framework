<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

use JsonSerializable;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SlackPhp\Framework\Auth\{AppCredentials, TokenStore};
use SlackPhp\BlockKit\Surfaces\Message;
use SlackPhp\Framework\Contexts\PayloadType;

/**
 * Provides a fluent, app builder interface.
 *
 * Acts as a façade to Application, AppConfig, and Router, creating a "one-stop shop" for configuring an application.
 */
class App extends Application
{
    private Router $router;

    /**
     * Creates a new, fluent-ready instance of App.
     *
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }

    private function __construct()
    {
        $this->router = new Router();
        parent::__construct($this->router);
    }

    // Proxied methods for AppConfig.

    /**
     * Sets the prefix to use for fetching environment variables.
     *
     * The default is "SLACK", but setting an app-specific one may be necessary for multi-tenant apps.
     *
     * @param string $prefix
     * @return $this
     */
    public function withEnvPrefix(string $prefix): self
    {
        $this->config->withEnvPrefix($prefix);

        return $this;
    }

    /**
     * Sets the App ID.
     *
     * Typically not required, as it will either be set for you or not required. You can set this explicitly to a) make
     * sure it gets included in log messages, and b) make sure the app validates that incoming requests match IDs.
     *
     * @param string $id
     * @return $this
     */
    public function withId(string $id): self
    {
        $this->config->withId($id);

        return $this;
    }

    /**
     * Sets a human-readable app alias to be used in log messages.
     *
     * @param string $alias
     * @return $this
     */
    public function withAlias(string $alias): self
    {
        $this->config->withAlias($alias);

        return $this;
    }

    /**
     * Sets the PSR-3 logger instance to use with the app.
     *
     * The PSR-3 logger gets wrapped by a custom logger implementation that adds additional context to messages.
     *
     * @param LoggerInterface $logger
     * @return $this
     */
    public function withLogger(LoggerInterface $logger): self
    {
        $this->config->withLogger($logger);

        return $this;
    }

    /**
     * Sets the PSR-11 container to use to resolve Listener class names to Listener class instances.
     *
     * This is only needed if you are using class names to configure Listeners AND those Listeners require dependencies
     * to be configured/resolved. The container is also accessible to the Context, if you need to use it as a "service
     * locator" as well (possible, but discouraged).
     *
     * @param ContainerInterface $container
     * @return $this
     */
    public function withContainer(ContainerInterface $container): self
    {
        $this->config->withContainer($container);

        return $this;
    }

    /**
     * Sets a TokenStore for the app, which is used as a service to get a "bot token" for a given team/enterprise ID.
     *
     * A TokenStore is needed when an app is distributed to more than one team (aka workspace) or enterprise org. In
     * other words, when more than one API token can be used, the Token Store is what provides it.
     *
     * @param TokenStore $tokenStore
     * @return $this
     */
    public function withTokenStore(TokenStore $tokenStore): self
    {
        $this->config->withTokenStore($tokenStore);

        return $this;
    }

    /**
     * Explicitly sets the signing key to use for Auth.
     *
     * You can also set this via the environment variable: SLACK_SIGNING_KEY.
     *
     * @param string $signingKey
     * @return $this
     */
    public function withSigningKey(string $signingKey): self
    {
        $this->config->withSigningKey($signingKey);

        return $this;
    }

    /**
     * Explicitly sets the client ID to use for OAuth.
     *
     * You can also set this via the environment variable: SLACK_CLIENT_ID.
     *
     * @param string $clientId
     * @return $this
     */
    public function withClientId(string $clientId): self
    {
        $this->config->withClientId($clientId);

        return $this;
    }

    /**
     * Explicitly sets the client secret to use for OAuth.
     *
     * You can also set this via the environment variable: SLACK_CLIENT_SECRET.
     *
     * @param string $clientSecret
     * @return $this
     */
    public function withClientSecret(string $clientSecret): self
    {
        $this->config->withClientSecret($clientSecret);

        return $this;
    }

    /**
     * Explicitly sets the state secret to use for OAuth.
     *
     * The "state secret" is a fixed value for your app that is sent to Slack during the OAuth flow and then verified
     * by the app when Slack redirects the user back to the app. You can also set this via the environment
     * variable: SLACK_STATE_SECRET.
     *
     * @param string $stateSecret
     * @return $this
     */
    public function withStateSecret(string $stateSecret): self
    {
        $this->config->withStateSecret($stateSecret);

        return $this;
    }

    /**
     * Sets the required scopes needed for the app. These are needed for the OAuth flow to set up app permissions.
     *
     * @param string[] $scopes
     * @return $this
     */
    public function withScopes(array $scopes): self
    {
        $this->config->withScopes($scopes);

        return $this;
    }

    /**
     * Explicitly sets the app token to use for Socket Mode auth.
     *
     * @param string $appToken
     * @return $this
     */
    public function withAppToken(string $appToken): self
    {
        $this->config->withAppToken($appToken);

        return $this;
    }

    /**
     * Explicitly sets the both token to use for Auth.
     *
     * You can also set this via the environment variable: SLACK_BOT_TOKEN.
     *
     * @param string $botToken
     * @return $this
     */
    public function withBotToken(string $botToken): self
    {
        $this->config->withBotToken($botToken);

        return $this;
    }

    /**
     * Sets the app credentials for the app.
     *
     * These credentials are an encapsulation of all the various app-specific keys and secrets needed for auth.
     *
     * @param AppCredentials $appCredentials
     * @return $this
     */
    public function withAppCredentials(AppCredentials $appCredentials): self
    {
        $this->config->withAppCredentials($appCredentials);

        return $this;
    }

    /**
     * Sets an "ack" message used for async commands to inform the user to wait for the result (e.g., "processing...").
     *
     * @param JsonSerializable|Message[]|Message|string|null $ack
     * @return $this
     */
    public function withCommandAck($ack): self
    {
        if ($ack instanceof JsonSerializable) {
            $ack = serialize($ack);
        }

        $this->router->withCommandAck($ack);

        return $this;
    }

    /**
     * Enables an interceptor to handle incoming "url_verification" requests and respond with the "challenge" value.
     *
     * @return $this
     */
    public function withUrlVerification(): self
    {
        $this->router->withUrlVerification();

        return $this;
    }

    /**
     * Configures a listener for an incoming "command" request.
     *
     * @param string $name
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function command(string $name, $listener): self
    {
        $this->router->command($name, $listener);

        return $this;
    }

    /**
     * Configures an async listener for an incoming "command" request.
     *
     * @param string $name
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function commandAsync(string $name, $listener): self
    {
        $this->router->commandAsync($name, $listener);

        return $this;
    }

    /**
     * Configures listeners for an incoming "command" request, based on sub-commands in the text.
     *
     * @param string $name
     * @param array<string, Listener|callable|class-string> $subCommands
     * @return $this
     */
    public function commandGroup(string $name, array $subCommands): self
    {
        $this->router->commandGroup($name, $subCommands);

        return $this;
    }

    /**
     * Configures async listeners for an incoming "command" request, based on sub-commands in the text.
     *
     * @param string $name
     * @param array<string, Listener|callable|class-string> $subCommands
     * @return $this
     */
    public function commandGroupAsync(string $name, array $subCommands): self
    {
        $this->router->commandGroupAsync($name, $subCommands);

        return $this;
    }

    /**
     * Configures a listener for an incoming "event" request.
     *
     * @param string $name
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function event(string $name, $listener): self
    {
        $this->router->event($name, $listener);

        return $this;
    }

    /**
     * Configures an async listener for an incoming "event" request.
     *
     * @param string $name
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function eventAsync(string $name, $listener): self
    {
        $this->router->eventAsync($name, $listener);

        return $this;
    }

    /**
     * Configures a listener for an incoming (global) "shortcut" request.
     *
     * @param string $callbackId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function globalShortcut(string $callbackId, $listener): self
    {
        $this->router->globalShortcut($callbackId, $listener);

        return $this;
    }

    /**
     * Configures an async listener for an incoming (global) "shortcut" request.
     *
     * @param string $callbackId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function globalShortcutAsync(string $callbackId, $listener): self
    {
        $this->router->globalShortcutAsync($callbackId, $listener);

        return $this;
    }

    /**
     * Configures a listener for an incoming "message_action" (aka message shortcut) request.
     *
     * @param string $callbackId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function messageShortcut(string $callbackId, $listener): self
    {
        $this->router->messageShortcut($callbackId, $listener);

        return $this;
    }

    /**
     * Configures an async listener for an incoming "message_action" (aka message shortcut) request.
     *
     * @param string $callbackId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function messageShortcutAsync(string $callbackId, $listener): self
    {
        $this->router->messageShortcutAsync($callbackId, $listener);

        return $this;
    }

    /**
     * Configures a listener for an incoming "block_actions" request.
     *
     * @param string $actionId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function blockAction(string $actionId, $listener): self
    {
        $this->router->blockAction($actionId, $listener);

        return $this;
    }

    /**
     * Configures an async listener for an incoming "block_actions" request.
     *
     * @param string $actionId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function blockActionAsync(string $actionId, $listener): self
    {
        $this->router->blockActionAsync($actionId, $listener);

        return $this;
    }

    /**
     * Configures a listener for an incoming "block_suggestion" request.
     *
     * @param string $actionId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function blockSuggestion(string $actionId, $listener): self
    {
        $this->router->blockSuggestion($actionId, $listener);

        return $this;
    }

    /**
     * Configures a listener for an incoming "view_submission" request.
     *
     * @param string $callbackId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function viewSubmission(string $callbackId, $listener): self
    {
        $this->router->viewSubmission($callbackId, $listener);

        return $this;
    }

    /**
     * Configures an async listener for an incoming "view_submission" request.
     *
     * @param string $callbackId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function viewSubmissionAsync(string $callbackId, $listener): self
    {
        $this->router->viewSubmissionAsync($callbackId, $listener);

        return $this;
    }

    /**
     * Configures a listener for an incoming "view_closed" request.
     *
     * @param string $callbackId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function viewClosed(string $callbackId, $listener): self
    {
        $this->router->viewClosed($callbackId, $listener);

        return $this;
    }

    /**
     * Configures an async listener for an incoming "view_closed" request.
     *
     * @param string $callbackId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function viewClosedAsync(string $callbackId, $listener): self
    {
        $this->router->viewClosedAsync($callbackId, $listener);

        return $this;
    }

    /**
     * Configures a listener for an incoming "workflow_step_edit" request.
     *
     * @param string $callbackId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function workflowStepEdit(string $callbackId, $listener): self
    {
        $this->router->workflowStepEdit($callbackId, $listener);

        return $this;
    }

    /**
     * Configures an async listener for an incoming "workflow_step_edit" request.
     *
     * @param string $callbackId
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function workflowStepEditAsync(string $callbackId, $listener): self
    {
        $this->router->workflowStepEditAsync($callbackId, $listener);

        return $this;
    }

    /**
     * Configures a listener for an incoming request of the specified type.
     *
     * @param PayloadType|string $type
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function on($type, $listener): self
    {
        $this->router->on($type, $listener);

        return $this;
    }

    /**
     * Configures an async listener for an incoming request of the specified type.
     *
     * @param PayloadType|string $type
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function onAsync($type, $listener): self
    {
        $this->router->onAsync($type, $listener);

        return $this;
    }

    /**
     * Configures a catch-all listener for an incoming request.
     *
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function any($listener): self
    {
        $this->router->any($listener);

        return $this;
    }

    /**
     * Configures an async catch-all listener for an incoming request.
     *
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function anyAsync($listener): self
    {
        $this->router->anyAsync($listener);

        return $this;
    }

    /**
     * Adds a tap interceptor, which executes a callback with the Context.
     *
     * @param callable(Context): void $callback
     * @return $this
     */
    public function tap(callable $callback): self
    {
        $this->router->tap($callback);

        return $this;
    }

    /**
     * Adds an interceptor that applies to all listeners in the Router.
     *
     * @param Interceptor $interceptor
     * @return $this
     */
    public function use(Interceptor $interceptor): self
    {
        $this->router->use($interceptor);

        return $this;
    }
}
