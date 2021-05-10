<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

use RuntimeException;
use Throwable;

class Exception extends RuntimeException
{
    protected array $context;

    public function __construct($message = "", $code = 0, Throwable $previous = null, array $context = [])
    {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function addContext(array $context): self
    {
        $this->context = $context + $this->context;

        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
