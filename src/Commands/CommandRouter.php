<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Commands;

use Closure;
use SlackPhp\Framework\{Coerce, Context, Listener};

use function array_keys;
use function array_map;
use function array_pop;
use function array_slice;
use function count;
use function explode;
use function implode;
use function max;
use function natsort;

/**
 * Routes commands to sub-routers (aka. sub-commands) based on params parsed from command text.
 */
class CommandRouter implements Listener
{
    private Listener $default;
    private string $description;
    private int $maxLevels;
    /** @var array<string, Listener> */
    private array $routes;

    public static function new(): self
    {
        return new self();
    }

    /**
     * @param array<string, Listener|callable(Context): void|class-string> $routes
     */
    public function __construct(array $routes = [])
    {
        $this->routes = [];
        $this->description = '';
        $this->maxLevels = 1;
        $this->add('help', Closure::fromCallable([$this, 'showHelp']));
        foreach ($routes as $subCommand => $listener) {
            $this->add($subCommand, $listener);
        }
    }

    /**
     * @param string $subCommand
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function add(string $subCommand, $listener): self
    {
        $listener = Coerce::listener($listener);
        if ($subCommand === '*') {
            $this->default = $listener;
        } else {
            $this->routes[$subCommand] = $listener;
            $this->maxLevels = max($this->maxLevels, count(explode(' ', $subCommand)));
        }

        return $this;
    }

    /**
     * @param Listener|callable(Context): void|class-string $listener
     * @return $this
     */
    public function withDefault($listener): self
    {
        $this->default = Coerce::listener($listener);

        return $this;
    }

    /**
     * @param string $description
     * @return self
     */
    public function withDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function handle(Context $context): void
    {
        $command = $context->payload()->get('command');
        $text = trim($context->payload()->get('text'));
        $nameArgs = array_slice(explode(' ', $text), 0, $this->maxLevels);

        // Match on the most specific (i.e., deepest) sub-command first, and then work backwards to the most generic.
        while (!empty($nameArgs)) {
            $subCommand = implode(' ', $nameArgs);
            if (isset($this->routes[$subCommand])) {
                $context->logger()->debug("CommandRouter routing to sub-command: \"{$command} {$subCommand}\"");
                $context->set('remaining_text', substr($text, strlen($subCommand) + 1));
                $this->routes[$subCommand]->handle($context);
                return;
            }
            array_pop($nameArgs);
        }

        if (isset($this->default)) {
            $this->default->handle($context);
        } else {
            $this->showHelp($context);
        }
    }

    private function showHelp(Context $context): void
    {
        $cmd = $context->payload()->get('command');
        $fmt = $context->fmt();
        $msg = $context->blocks()->message();

        $msg->header("The {$cmd} Command");
        if ($this->description) {
            $msg->text($this->description);
        }

        $routes = array_keys($this->routes);
        natsort($routes);
        $msg->newSection()->mrkdwnText($fmt->lines([
            '*Available sub-commands*:',
            $fmt->bulletedList(array_map(fn (string $subCommand) => $fmt->code("{$cmd} {$subCommand}"), $routes))
        ]));

        if ($context->isAcknowledged()) {
            $context->respond($msg);
        } else {
            $context->ack($msg);
        }
    }
}
