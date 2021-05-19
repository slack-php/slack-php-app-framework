<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Deferral;

use Closure;
use SlackPhp\Framework\{AppServer, Context, Exception};
use SlackPhp\Framework\Auth\TokenStore;
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
    private TokenStore $tokenStore;

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

    /**
     * @param TokenStore $tokenStore
     * @return $this
     */
    public function withTokenStore(TokenStore $tokenStore): self
    {
        $this->tokenStore = $tokenStore;

        return $this;
    }

    protected function init(): void
    {
        global $argv;
        $this->args = $argv ?? [];
    }

    public function start(): void
    {
        try {
            $this->getLogger()->debug('Started processing of deferred context');
            $context = $this->deserializeContext($this->args[1] ?? '');
            if (isset($this->tokenStore)) {
                $context->withTokenStore($this->tokenStore);
            }
            $this->getApp()->handle($context);
            $this->getLogger()->debug('Completed processing of deferred context');
        } catch (Throwable $exception) {
            $this->getLogger()->error('Error occurred during processing of deferred context', compact('exception'));
            $this->exitCode = 1;
        }

        $this->stop();
    }

    public function stop(): void
    {
        if (isset($this->args[2]) && $this->args[2] === '--soft-exit') {
            return;
        }

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
