<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

use SlackPhp\BlockKit\Surfaces\Message;
use SlackPhp\Framework\Contexts\PayloadType;

/**
 * Routes app contexts by their payload type and IDs.
 */
class Router implements Listener
{
    private const DEFAULT = '_default';

    /** @var array<string, array<string, Listener>> */
    private array $listeners;

    private ?Listener $commandAck = null;
    private Interceptors\Chain $interceptors;
    private bool $urlVerificationAdded = false;

    /**
     * @return static
     */
    public static function new(): self
    {
        return new static();
    }

    final public function __construct()
    {
        $this->listeners = [];
        $this->interceptors = Interceptors\Chain::new();
    }

    /**
     * Sets an "ack" message used for async commands to inform the user to wait for the result (e.g., "processing...").
     *
     * @param Message[]|Message|string|null $ack
     * @return $this
     */
    public function withCommandAck($ack): self
    {
        $this->commandAck = new Listeners\Ack($ack);

        return $this;
    }

    /**
     * Enables an interceptor to handle incoming "url_verification" requests and respond with the "challenge" value.
     *
     * @return $this
     */
    public function withUrlVerification(): self
    {
        if (!$this->urlVerificationAdded) {
            $this->interceptors->add(new Interceptors\UrlVerification(), true);
            $this->urlVerificationAdded = true;
        }

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
        return $this->register(PayloadType::command(), $name, $listener);
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
        return $this->command($name, Route::async($listener, $this->commandAck));
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
        return $this->register(PayloadType::command(), $name, new Commands\CommandRouter($subCommands));
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
        return $this->register(PayloadType::command(), $name, Route::async(
            new Commands\CommandRouter($subCommands),
            $this->commandAck
        ));
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
        return $this->withUrlVerification()->register(PayloadType::eventCallback(), $name, $listener);
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
        return $this->event($name, Route::async($listener));
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
        return $this->register(PayloadType::shortcut(), $callbackId, $listener);
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
        return $this->globalShortcut($callbackId, Route::async($listener));
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
        return $this->register(PayloadType::messageAction(), $callbackId, $listener);
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
        return $this->messageShortcut($callbackId, Route::async($listener));
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
        return $this->register(PayloadType::blockActions(), $actionId, $listener);
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
        return $this->blockAction($actionId, Route::async($listener));
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
        return $this->register(PayloadType::blockSuggestion(), $actionId, $listener);
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
        return $this->register(PayloadType::viewSubmission(), $callbackId, $listener);
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
        return $this->viewSubmission($callbackId, Route::async($listener));
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
        return $this->register(PayloadType::viewClosed(), $callbackId, $listener);
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
        return $this->viewClosed($callbackId, Route::async($listener));
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
        return $this->register(PayloadType::workflowStepEdit(), $callbackId, $listener);
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
        return $this->workflowStepEdit($callbackId, Route::async($listener));
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
        return $this->register($type, self::DEFAULT, $listener);
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
        return $this->on($type, Route::async($listener));
    }

    /**
     * Configures a catch-all listener for an incoming request.
     *
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function any($listener): self
    {
        return $this->register(self::DEFAULT, self::DEFAULT, $listener);
    }

    /**
     * Configures an async catch-all listener for an incoming request.
     *
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function anyAsync($listener): self
    {
        return $this->any(Route::async($listener));
    }

    /**
     * Adds a tap interceptor, which executes a callback with the Context.
     *
     * @param callable(Context): void $callback
     * @return $this
     */
    public function tap(callable $callback): self
    {
        return $this->use(new Interceptors\Tap($callback));
    }

    /**
     * Adds an interceptor that applies to all listeners in the Router.
     *
     * @param Interceptor $interceptor
     * @return $this
     */
    public function use(Interceptor $interceptor): self
    {
        $this->interceptors->add($interceptor);

        return $this;
    }

    public function handle(Context $context): void
    {
        $this->getListener($context)->handle($context);
    }

    /**
     * @param Context $context
     * @return Listener
     */
    public function getListener(Context $context): Listener
    {
        $type = (string) $context->payload()->getType();
        $id = $context->payload()->getTypeId() ?? self::DEFAULT;
        $listener = $this->listeners[$type][$id]
            ?? $this->listeners[$type][self::DEFAULT]
            ?? $this->listeners[self::DEFAULT][self::DEFAULT]
            ?? new Listeners\Undefined();

        return new Listeners\Intercepted($this->interceptors, $listener);
    }

    /**
     * @param PayloadType|string $type
     * @param string $name
     * @param Listener|callable|class-string|null $listener
     * @return $this
     */
    private function register($type, string $name, $listener): self
    {
        $type = (string) $type;
        $name = trim($name, '/ ');
        $this->listeners[$type][$name] = Coerce::listener($listener);

        return $this;
    }
}
