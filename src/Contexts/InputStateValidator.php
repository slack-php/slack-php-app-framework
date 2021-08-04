<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

/**
 * Validates `view_submission` input state using configured rules.
 */
class InputStateValidator
{
    private array $rules;

    public static function new(): self
    {
        return new self();
    }

    /**
     * Adds a validation rule for an input state field.
     *
     * @param string $key The block ID for the input in the state to validate.
     * @param bool $required Whether the input is required.
     * @param null|callable(string|string[]): ?string $ruleFn Function to validate the input from the state and returns
     *                                                        either an error message string or null (for no error).
     * @return $this
     */
    public function rule(string $key, bool $required, ?callable $ruleFn = null): self
    {
        $this->rules[$key] = function ($value) use ($required, $ruleFn): ?string {
            if ($required && !isset($value)) {
                return 'This value is required.';
            }

            if (isset($ruleFn, $value)) {
                return $ruleFn($value);
            }

            return null;
        };

        return $this;
    }

    /**
     * Validates an input state using the configured rule set.
     *
     * @param InputState $state The input state to validate.
     * @throws InputStateValidationException if the input state does not pass all rule validations.
     */
    public function validate(InputState $state): void
    {
        $errors = [];
        foreach ($state->toArray() as $key => $value) {
            $error = isset($this->rules[$key]) ? $this->rules[$key]($value) : null;
            if (is_string($error)) {
                $errors[$key] = $error;
            }
        }

        if (count($errors) > 0) {
            throw new InputStateValidationException($state, $errors);
        }
    }
}
