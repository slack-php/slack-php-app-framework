<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Deferral;

use Closure;
use SlackPhp\Framework\{Context, Deferrer, Exception};

use function escapeshellarg;
use function base64_encode;
use function is_dir;
use function json_encode;
use function shell_exec;

/**
 * Defers context processing for async logic by shelling out to an external, background-running script.
 */
class ShellExecDeferrer implements Deferrer
{
    private string $dir;
    private string $script;
    private ?Closure $serializeCallback;

    /**
     * @param string $dir Directory to `cd` to before running the script.
     * @param string $script Script to run for processing the deferred context.
     */
    public function __construct(string $dir, string $script, ?callable $serializeCallback = null)
    {
        $this->script = $script;
        $this->dir = $dir;
        if (!is_dir($this->dir)) {
            throw new Exception('Invalid dir for deferrer script');
        }

        $this->serializeCallback = $serializeCallback ? Closure::fromCallable($serializeCallback) : null;
    }

    public function defer(Context $context): void
    {
        $context->logger()->debug('Deferring processing by running a command with shell_exec in the background');
        $data = escapeshellarg($this->serializeContext($context));
        $command = "cd {$this->dir};nohup {$this->script} {$data} > /dev/null &";
        shell_exec($command);
    }

    private function serializeContext(Context $context): string
    {
        $fn = $this->serializeCallback ?? fn (Context $ctx): string => base64_encode((string)json_encode($ctx->toArray()));

        return $fn($context);
    }
}
