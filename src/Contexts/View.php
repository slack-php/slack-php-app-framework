<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

use SlackPhp\BlockKit\Surfaces\Modal;
use JsonException;
use SlackPhp\Framework\{Coerce, Context};

class View
{
    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function clear(): void
    {
        $this->context->ack([
            'response_action' => 'clear',
        ]);
    }

    /**
     * @throws JsonException
     */
    public function close(): void
    {
        $this->context->ack();
    }

    /**
     * @param string[] $errors
     *
     * @throws JsonException
     */
    public function errors(array $errors): void
    {
        $this->context->ack([
            'response_action' => 'errors',
            'errors' => $errors,
        ]);
    }

    /**
     * @param Modal[]|(callable(): Modal)|Modal|string $modal
     *
     * @throws JsonException
     */
    public function push($modal): void
    {
        $this->context->ack([
            'response_action' => 'push',
            'view' => Coerce::modal($modal),
        ]);
    }

    /**
     * @param Modal|Modal[]|string|callable(): Modal $modal
     *
     * @throws JsonException
     */
    public function update($modal): void
    {
        $this->context->ack([
            'response_action' => 'update',
            'view' => Coerce::modal($modal),
        ]);
    }
}
