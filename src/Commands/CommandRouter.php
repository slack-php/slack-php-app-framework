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
    /** @var array<string, Listener> */
    private array $routes;
    private string $description;
    private int $maxLevels;

    public static function new(): self
    {
        return new self();
    }

    public function __construct(array $routes = [])
    {
        $this->routes = [];
        $this->description = '';
        $this->maxLevels = 1;
        $this->add('list', Closure::fromCallable([$this, 'listSubCommands']));
        foreach ($routes as $subCommand => $listener) {
            $this->add($subCommand, $listener);
        }
    }

    /**
     * @param string $subCommand
     * @param Listener|callable|class-string $listener
     * @return $this
     */
    public function add(string $subCommand, $listener): self
    {
        $this->routes[$subCommand] = Coerce::listener($listener);
        $this->maxLevels = max($this->maxLevels, count(explode(' ', $subCommand)));

        return $this;
    }

    /**
     * @param string $description
     * @return self
     */
    public function description(string $description): self
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

        $context->logger()->debug('CommandRouter could not find sub-command; routing to "list" instead');
        $this->listSubCommands($context);
    }

    private function listSubCommands(Context $ctx): void
    {
        $cmd = $ctx->payload()->get('command');
        $fmt = $ctx->fmt();
        $msg = $ctx->blocks()->message()->header("The {$cmd} Command");
        if ($this->description) {
            $msg->text($this->description);
        }

        $routes = array_keys($this->routes);
        natsort($routes);

        $msg->newSection()->mrkdwnText($fmt->lines([
            '*Available commands*:',
            $fmt->bulletedList(array_map(fn (string $subCommand) => $fmt->code("{$cmd} {$subCommand}"), $routes))
        ]));

        if ($ctx->isAcknowledged()) {
            $ctx->respond($msg);
        } else {
            $ctx->ack($msg);
        }
    }
}
