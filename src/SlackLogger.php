<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

use Psr\Log\{AbstractLogger, LoggerInterface, NullLogger};

class SlackLogger extends AbstractLogger
{
    /**
     * @var string[]
     */
    private array $context;

    private LoggerInterface $logger;
    private string $name;

    public static function wrap(?LoggerInterface $logger): self
    {
        if (!$logger instanceof self) {
            $logger = new self($logger);
        }

        return $logger;
    }

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->name = 'App';
        $this->context = [];
    }

    public function unwrap(): LoggerInterface
    {
        return $this->logger;
    }

    public function withInternalLogger(LoggerInterface $logger): self
    {
        if ($logger instanceof self) {
            $logger = $logger->unwrap();
        }

        $this->logger = $logger;

        return $this;
    }

    public function withName(?string $name): self
    {
        $this->name = $name ?? 'App';

        return $this;
    }

    /**
     * @deprecated use addContext() instead
     *
     * @param string[] $context
     */
    public function withData(array $context): self
    {
        $this->context = $context + $this->context;

        return $this;
    }

    /**
     * @param string[] $context
     */
    public function addContext(array $context): self
    {
        $this->context = $context + $this->context;

        return $this;
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, "[{$this->name}] {$message}", $context + $this->context);
    }
}
