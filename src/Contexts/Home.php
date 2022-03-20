<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

use SlackPhp\BlockKit\Surfaces\AppHome;
use SlackPhp\Framework\{Coerce, Context, Exception};
use Throwable;

/**
 * Provides simple access to App Home APIs: "views.publish".
 */
class Home
{
    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Updates the user's App Home without a hash check.
     *
     * This is essentially a force update.
     *
     * @param AppHome|AppHome[]|string|callable(): AppHome $appHome App Home content.
     * @param string|null $userId The ID for the user that will have their App Home updated. Defaults to current user.
     * @return bool
     */
    public function update($appHome, ?string $userId = null): bool
    {
        return $this->callViewsPublishApi(Coerce::appHome($appHome), $userId, null);
    }

    /**
     * Updates the user's App Home using a hash check.
     *
     * This includes a hash check, which means that if the App Home is updated in another process first, then this API
     * call will fail due to the hash not matching.
     *
     * @param AppHome|AppHome[]|string|callable(): AppHome $appHome App Home content.
     * @param string|null $userId The ID for the user that will have their App Home updated. Defaults to current user.
     * @param string|null $hash The hash for Slack to verify for conditional updates.
     * @return bool
     */
    public function safeUpdate($appHome, ?string $userId = null, ?string $hash = null): bool
    {
        $hash = $hash ?? $this->context->payload()->get('view.hash', true);

        return $this->callViewsPublishApi(Coerce::appHome($appHome), $userId, $hash);
    }

    /**
     * Updates the user's App Home using a hash check, but does not throw an exception is the hash check fails.
     *
     * This is a "best effort" approach where the hash check still occurs, but failing to update the App Home is not
     * considered an error state.
     *
     * @param AppHome|AppHome[]|string|callable(): AppHome $appHome App Home content.
     * @param string|null $userId The ID for the user that will have their App Home updated. Defaults to current user.
     * @param string|null $hash The hash for Slack to verify for conditional updates.
     * @return bool
     */
    public function updateIfSafe($appHome, ?string $userId = null, ?string $hash = null): bool
    {
        try {
            return $this->safeUpdate($appHome, $userId, $hash);
        } catch (Throwable $ex) {
            return false;
        }
    }

    /**
     * Makes the API call to "views.publish" for updating an App Home.
     *
     * @param AppHome $appHome App Home content.
     * @param string|null $userId The ID for the user that will have their App Home updated. Defaults to current user.
     * @param string|null $hash The hash for Slack to verify for conditional updates.
     * @return bool
     */
    private function callViewsPublishApi(AppHome $appHome, ?string $userId, ?string $hash): bool
    {
        $payload = $this->context->payload();

        try {
            $params = [
                'user_id' => $userId ?? $payload->getUserId(),
                'view' => $appHome->toArray(),
            ];

            if ($hash !== null) {
                $params['hash'] = $hash;
            }

            $result = $this->context->api('views.publish', $params);

            return (bool) ($result['ok'] ?? false);
        } catch (Throwable $ex) {
            throw new Exception('API call to `views.publish` failed', 0, $ex);
        }
    }
}
