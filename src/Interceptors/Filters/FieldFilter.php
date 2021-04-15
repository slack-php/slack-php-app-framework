<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Interceptors\Filters;

use SlackPhp\Framework\{Context, Listener};
use SlackPhp\Framework\Interceptors\Filter;

class FieldFilter extends Filter
{
    /** @var array<string, mixed> */
    private $fields;

    /**
     * @param array<string, mixed> $fields
     * @param Listener|callable|class-string|null $defaultListener
     */
    public function __construct(array $fields, $defaultListener = null)
    {
        parent::__construct($defaultListener);
        $this->fields = $fields;
    }

    public function matches(Context $context): bool
    {
        foreach ($this->fields as $field => $value) {
            $matched = substr($value, 0, 6) === 'regex:'
                ? $this->matchRegex($context, $field, substr($value, 6))
                : $this->matchValue($context, $field, $value);

            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    private function matchValue(Context $context, string $field, string $value): bool
    {
        $result = true;
        if (substr($value, 0, 4) === 'not:') {
            $result = false;
            $value = substr($value, 4);
        }

        return ($context->payload()->get($field) === $value) === $result;
    }

    private function matchRegex(Context $context, string $field, string $regex): bool
    {
        if (preg_match($regex, $context->payload()->get($field), $matches)) {
            $allMatches = $context->get('regex') ?? [];
            $allMatches[$field] = $matches;
            $context->set('regex', $allMatches);

            return true;
        }

        return false;
    }
}
