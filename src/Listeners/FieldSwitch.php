<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Listeners;

use Closure;
use SlackPhp\Framework\{Coerce, Context, Listener};

class FieldSwitch implements Listener
{
    /** @var array<string, Listener> */
    private array $cases;

    private ?Listener $default;
    private string $field;

    /**
     * @phpstan-param array<Listener|callable(Context): void|class-string> $cases
     * @param null|Listener|callable(Context): void|class-string $default
     */
    public function __construct(string $field, array $cases, $default = null)
    {
        $default ??= $cases['*'] ?? null;
        if ($default !== null) {
            $this->default = Coerce::listener($default);
            unset($cases['*']);
        }

        $this->field = $field;
        $this->cases = array_map(Closure::fromCallable([Coerce::class, 'listener']), $cases);
    }

    public function handle(Context $context): void
    {
        $value = $context->payload()->get($this->field);
        $listener = $this->cases[$value] ?? $this->default ?? new Undefined();
        $listener->handle($context);
    }
}
