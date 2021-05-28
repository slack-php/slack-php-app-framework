<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Deferral;

use Closure;
use SlackPhp\Framework\{AppServer, Context, Exception};
use Throwable;

/**
 * Server implementation meant to be run from the CLI to process a deferred context.
 */
class DeferredContextCliServer extends AppServer
{
    /** @var string[] */
    private array $args;
    private ?Closure $deserializeCallback;
    private int $exitCode = 0;

    /**
     * @param string[] $args
     * @return $this
     */
    public function withArgs(array $args): self
    {
        $this->args = $args;

        return $this;
    }

    /**
     * @param callable(string): Context $deserializeCallback
     * @return $this
     */
    public function withDeserializeCallback(callable $deserializeCallback): self
    {
        $this->deserializeCallback = Closure::fromCallable($deserializeCallback);

        return $this;
    }

    protected function init(): void
    {
        global $argv;
        $this->args = $argv ?? [];
    }

    public function start(): void
    {
        // Process args.
        $serializedContext = $this->args[1] ?? '';
        $softExit = ($this->args[2] ?? '') === '--soft-exit';

        // Run the app.
        try {
            $this->getLogger()->debug('Started processing of deferred context');
            $context = $this->deserializeContext($serializedContext);
            $this->getAppCredentials();
            $this->getApp()->handle($context);
            $this->getLogger()->debug('Completed processing of deferred context');
        } catch (Throwable $exception) {
            $this->getLogger()->error('Error occurred during processing of deferred context', compact('exception'));
            $this->exitCode = 1;
        }

        if (!$softExit) {
            $this->stop();
        }
    }

    /**
     * @return never-returns
     */
    public function stop(): void
    {
        exit($this->exitCode);
    }

    private function deserializeContext(string $serializedContext): Context
    {
        $fn = $this->deserializeCallback ?? function (string $serializedContext): Context {
            if (strlen($serializedContext) === 0) {
                throw new Exception('No context provided');
            }

            $data = json_decode(base64_decode($serializedContext), true);
            if (empty($data)) {
                throw new Exception('Invalid context data');
            }

            $context = Context::fromArray($data);
            if (!($context->isAcknowledged() && $context->isDeferred())) {
                throw new Exception('Context was not deferred');
            }

            return $context;
        };

        return $fn($serializedContext);
    }
}
