<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Interceptors\Filters;

use Closure;
use SlackPhp\Framework\{Context, Listener};
use SlackPhp\Framework\Interceptors\Filter;

class CallbackFilter extends Filter
{
    /** @var Closure */
    private $filterFn;

    /**
     * @param callable $filterFn
     * @param Listener|callable|class-string|null $defaultListener
     */
    public function __construct(callable $filterFn, $defaultListener = null)
    {
        $this->filterFn = $filterFn instanceof Closure ? $filterFn : Closure::fromCallable($filterFn);
        parent::__construct($defaultListener);
    }

    public function matches(Context $context): bool
    {
        return ($this->filterFn)($context);
    }
}
