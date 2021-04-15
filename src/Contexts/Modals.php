<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

use SlackPhp\BlockKit\Surfaces\Modal;
use SlackPhp\Framework\{Coerce, Context, Exception};
use Throwable;

class Modals
{
    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param Modal|array|string $modal
     * @param string|null $triggerId
     * @return array
     */
    public function open($modal, ?string $triggerId = null): array
    {
        try {
            $triggerId ??= (string) $this->context->payload()->get('trigger_id', true);
            $result = $this->context->api('views.open', [
                'trigger_id' => $triggerId,
                'view' => Coerce::modal($modal)->toArray(),
            ]);

            return $result['view'] ?? [];
        } catch (Throwable $ex) {
            throw new Exception('Slack API call to `views.open` failed', 0, $ex);
        }
    }

    /**
     * @param Modal|array|string $modal
     * @param string|null $triggerId
     * @return array
     */
    public function push($modal, ?string $triggerId = null): array
    {
        try {
            $triggerId ??= (string) $this->context->payload()->get('trigger_id', true);
            $result = $this->context->api('views.push', [
                'trigger_id' => $triggerId,
                'view' => Coerce::modal($modal)->toArray(),
            ]);

            return $result['view'] ?? [];
        } catch (Throwable $ex) {
            throw new Exception('Slack API call to `views.push` failed', 0, $ex);
        }
    }

    /**
     * @param Modal|array|string $modal
     * @param string|null $viewId
     * @param string|null $hash
     * @param string|null $externalId
     * @return array
     */
    public function update($modal, ?string $viewId = null, ?string $hash = null, ?string $externalId = null): array
    {
        $payload = $this->context->payload();

        try {
            if ($externalId !== null) {
                $viewId = null;
            } else {
                $viewId ??= (string) $payload->get('view.id', true);
            }

            $result = $this->context->api('views.update', array_filter([
                'view_id' => $viewId,
                'external_id' => $externalId,
                'view' => Coerce::modal($modal)->toArray(),
                'hash' => $payload->get('view.hash'),
            ]));

            return $result['view'] ?? [];
        } catch (Throwable $ex) {
            throw new Exception('Slack API call to `views.update` failed', 0, $ex);
        }
    }
}
