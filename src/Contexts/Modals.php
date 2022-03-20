<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

use SlackPhp\BlockKit\Surfaces\Modal;
use SlackPhp\Framework\{Coerce, Context, Exception};
use Throwable;

/**
 * Provides simple access to modal APIs: "views.open", "views.push", and "views.update".
 */
class Modals
{
    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Opens a new modal.
     *
     * @param Modal|Modal[]|string|callable(): Modal $modal Modal content.
     * @param string|null $triggerId Non-expired trigger ID. Defaults to the trigger ID from the current payload.
     * @return mixed[]
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
     * Pushes a new modal onto the modal stack.
     *
     * Note: A modal stack can have up to 3 modals.
     *
     * @param Modal|Modal[]|string|callable(): Modal $modal Modal content.
     * @param string|null $triggerId Non-expired trigger ID. Defaults to the trigger ID from the current payload.
     * @return mixed[]
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
     * Updates an existing modal without a hash check.
     *
     * This is essentially a force update.
     *
     * @param Modal|Modal[]|string|callable(): Modal $modal Modal content.
     * @param string|null $viewId The modal's ID. Defaults to the view ID from the current payload.
     * @param string|null $extId The custom external ID for the modal, if one was assigned.
     * @return mixed[]
     */
    public function update($modal, ?string $viewId = null, ?string $extId = null): array
    {
        return $this->callViewsUpdateApi(Coerce::modal($modal), $viewId, null, $extId);
    }

    /**
     * Updates an existing modal using a hash check.
     *
     * This includes a hash check, which means that if the modal is updated in another process first, then this API call
     * will fail due to the hash not matching.
     *
     * @param Modal|Modal[]|string|callable(): Modal $modal Modal content.
     * @param string|null $viewId The modal's ID. Defaults to the view ID from the current payload.
     * @param string|null $hash The hash for Slack to verify for conditional updates.
     * @param string|null $extId The custom external ID for the modal, if one was assigned.
     * @return mixed[]
     */
    public function safeUpdate($modal, ?string $viewId = null, ?string $hash = null, ?string $extId = null): array
    {
        $hash = $hash ?? $this->context->payload()->get('view.hash', true);

        return $this->callViewsUpdateApi(Coerce::modal($modal), $viewId, $hash, $extId);
    }

    /**
     * Updates an existing modal using a hash check, but does not throw an exception is the hash check fails.
     *
     * This is a "best effort" approach where the hash check still occurs, but failing to update the modal is not
     * considered an error state.
     *
     * @param Modal|Modal[]|string|callable(): Modal $modal Modal content.
     * @param string|null $viewId The modal's ID. Defaults to the view ID from the current payload.
     * @param string|null $hash The hash for Slack to verify for conditional updates.
     * @param string|null $extId The custom external ID for the modal, if one was assigned.
     * @return mixed[]
     */
    public function updateIfSafe($modal, ?string $viewId = null, ?string $hash = null, ?string $extId = null): array
    {
        try {
            return $this->safeUpdate($modal, $viewId, $hash, $extId);
        } catch (Throwable $ex) {
            return [];
        }
    }

    /**
     * Makes the API call to "views.update" for updating a modal.
     *
     * @param Modal $modal Modal content.
     * @param string|null $viewId The modal's ID. Defaults to the view ID from the current payload.
     * @param string|null $hash The hash for Slack to verify for conditional updates.
     * @param string|null $externalId The custom external ID for the modal, if one was assigned.
     * @return mixed[]
     */
    private function callViewsUpdateApi(Modal $modal, ?string $viewId, ?string $hash, ?string $externalId): array
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
                'view' => $modal->toArray(),
                'hash' => $hash,
            ]));

            return $result['view'] ?? [];
        } catch (Throwable $ex) {
            throw new Exception('Slack API call to `views.update` failed', 0, $ex);
        }
    }
}
