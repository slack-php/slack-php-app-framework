<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

use JsonSerializable;
use SlackPhp\Framework\Context;

/**
 * Normalizes state data from all input types in `view_submission`s to key-value pairs.
 */
class InputState implements JsonSerializable
{
    use HasData;

    /**
     * Creates the InputState by extracting the data from the Context.
     *
     * @param Context $context
     * @return self
     */
    public static function fromContext(Context $context): self
    {
        return new self($context->payload()->getState()->toArray());
    }

    /**
     * @param array<string, mixed> $stateData
     */
    public function __construct(array $stateData)
    {
        $data = [];
        foreach ($stateData as $blockId => $input) {
            $elem = reset($input);
            $data[$blockId] = $elem['value']
                ?? $elem['selected_option']['value']
                ?? $elem['selected_date']
                ?? $elem['selected_time']
                ?? $elem['selected_user']
                ?? $elem['selected_conversation']
                ?? $elem['selected_channel']
                ?? null;
            if ($data[$blockId] !== null) {
                continue;
            }

            if (isset($elem['selected_options'])) {
                $data[$blockId] = array_column($elem['selected_options'], 'value');
                continue;
            }

            $data[$blockId] = $elem['selected_users']
                ?? $elem['selected_conversations']
                ?? $elem['selected_channels']
                ?? null;
        }

        $this->setData($data);
    }
}
