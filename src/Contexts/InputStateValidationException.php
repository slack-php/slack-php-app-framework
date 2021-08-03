<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

use SlackPhp\Framework\Exception;

/**
 * Exception that contains errors from `view_submission` input state.
 */
class InputStateValidationException extends Exception
{
    /**
     * @param InputState $state
     * @param array<string, string> $errors
     */
    public function __construct(InputState $state, array $errors)
    {
        parent::__construct('Input state failed validation', 0, null, [
            'state' => $state->toArray(),
            'errors' => $errors,
        ]);
    }

    /**
     * Gets any validation errors that can be returned to Slack in the "response_action".
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->getContext()['errors'] ?? [];
    }
}
