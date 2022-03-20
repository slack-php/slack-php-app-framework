<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

use RuntimeException;
use Throwable;

class Exception extends RuntimeException
{
    /**
     * @var array<mixed, mixed> $context
     */
    protected array $context;

    /**
     * @param array<mixed, mixed> $context
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null, array $context = [])
    {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param array<mixed, mixed> $context
     */
    public function addContext(array $context): self
    {
        $this->context = $context + $this->context;

        return $this;
    }

    /**
     * @return array<mixed, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
