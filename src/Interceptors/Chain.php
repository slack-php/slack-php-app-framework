<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Interceptors;

use SlackPhp\Framework\{Context, Interceptor, Listener};
use SlackPhp\Framework\Listeners\Intercepted;

class Chain implements Interceptor
{
    /** @var Interceptor[] */
    private array $interceptors = [];

    public static function new(): self
    {
        return new self();
    }

    /**
     * @param Interceptor[] $interceptors
     */
    public function __construct(array $interceptors = [])
    {
        $this->addMultiple($interceptors);
    }

    public function add(Interceptor $interceptor, bool $prepend = false): self
    {
        if ($interceptor instanceof self) {
            return $this->addMultiple($interceptor->interceptors, $prepend);
        }

        if ($prepend) {
            array_unshift($this->interceptors, $interceptor);
        } else {
            $this->interceptors[] = $interceptor;
        }

        return $this;
    }

    /**
     * @param Interceptor[] $interceptors
     */
    public function addMultiple(array $interceptors, bool $prepend = false): self
    {
        foreach ($interceptors as $interceptor) {
            $this->add($interceptor, $prepend);
        }

        return $this;
    }

    public function intercept(Context $context, Listener $listener): void
    {
        $interceptors = $this->interceptors;
        while ($interceptor = array_pop($interceptors)) {
            $listener = new Intercepted($interceptor, $listener);
        }

        $listener->handle($context);
    }
}
