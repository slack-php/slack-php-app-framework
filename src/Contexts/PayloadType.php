<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

final class PayloadType
{
    /** @var array<string, self|null> */
    private static array $instances = [
        'app_rate_limited' => null,
        'block_actions' => null,
        'block_suggestion' => null,
        'command' => null,
        'event_callback' => null,
        'interactive_message' => null,
        'message_action' => null,
        'shortcut' => null,
        'unknown' => null,
        'url_verification' => null,
        'view_closed' => null,
        'view_submission' => null,
        'workflow_step_edit' => null,
    ];

    /** @var array<string, string> */
    private static array $idFields = [
        'block_actions' => 'actions.0.action_id',
        'block_suggestion' => 'action_id',
        'command' => 'command',
        'event_callback' => 'event.type',
        'message_action' => 'callback_id',
        'shortcut' => 'callback_id',
        'view_closed' => 'view.callback_id',
        'view_submission' => 'view.callback_id',
        'workflow_step_edit' => 'callback_id',
    ];

    private string $value;

    /**
     * Get the instance of the enum with the provided value.
     *
     * @param string $value
     * @return self
     */
    public static function withValue(string $value): self
    {
        if (!array_key_exists($value, self::$instances)) {
            $value = 'unknown';
        }

        if (!isset(self::$instances[$value])) {
            self::$instances[$value] = new self($value);
        }

        return self::$instances[$value];
    }

    public static function appRateLimited(): self
    {
        return self::withValue('app_rate_limited');
    }

    public static function blockActions(): self
    {
        return self::withValue('block_actions');
    }

    public static function blockSuggestion(): self
    {
        return self::withValue('block_suggestion');
    }

    public static function command(): self
    {
        return self::withValue('command');
    }

    public static function eventCallback(): self
    {
        return self::withValue('event_callback');
    }

    public static function interactiveMessage(): self
    {
        return self::withValue('interactive_message');
    }

    public static function messageAction(): self
    {
        return self::withValue('message_action');
    }

    public static function shortcut(): self
    {
        return self::withValue('shortcut');
    }

    public static function unknown(): self
    {
        return self::withValue('unknown');
    }

    public static function urlVerification(): self
    {
        return self::withValue('url_verification');
    }

    public static function viewClosed(): self
    {
        return self::withValue('view_closed');
    }

    public static function viewSubmission(): self
    {
        return self::withValue('view_submission');
    }

    public static function workflowStepEdit(): self
    {
        return self::withValue('workflow_step_edit');
    }

    private function __construct(?string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function idField(): ?string
    {
        return self::$idFields[$this->value] ?? null;
    }
}
