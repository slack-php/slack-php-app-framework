<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

use ArrayAccess;
use Closure;
use JsonException;
use JsonSerializable;
use Psr\Container\ContainerInterface;
use SlackPhp\BlockKit\{Formatter, Kit};
use SlackPhp\BlockKit\Partials\OptionList;
use SlackPhp\BlockKit\Surfaces\{AppHome, Message};
use SlackPhp\Framework\Contexts\{
    Blocks,
    Error,
    HasData,
    Home,
    Modals,
    Payload,
    PayloadType,
    View,
};
use SlackPhp\Framework\Clients\{
    ApiClient,
    RespondClient,
    SimpleApiClient,
    SimpleRespondClient,
};
use Throwable;

use function array_filter;
use function implode;
use function in_array;
use function is_array;
use function json_encode;

/**
 * A Slack "context" provides an interface to all the data and affordances for an incoming Slack request/event.
 *
 * @implements ArrayAccess<mixed, mixed>
 */
class Context implements ArrayAccess, JsonSerializable
{
    use HasData {
        HasData::toArray as private getData;
    }

    private const ACKNOWLEDGED_KEY = '_acknowledged';
    private const APP_ID_KEY = '_app';
    private const DEFERRED_KEY = '_deferred';
    private const PAYLOAD_KEY = '_payload';
    private const SPECIAL_KEYS = [self::ACKNOWLEDGED_KEY, self::APP_ID_KEY, self::DEFERRED_KEY, self::PAYLOAD_KEY];

    private ?string $ack;
    private ?Closure $ackCallback;
    private ?ApiClient $apiClient;
    private ?string $appId;
    private ?Blocks $blocks;
    private AppConfig $appConfig;
    private bool $isAcknowledged;
    private bool $isDeferred;
    private Payload $payload;
    private ?RespondClient $respondClient;

    /**
     * Hydrate a Context from an array.
     *
     * This is primarily used in asynchronous processors, where an app is processing a request after it's been deferred
     * (e.g., to a queueing system). In this use case, the Context is likely being hydrated from deserialized JSON.
     *
     * @param mixed[] $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $payload = new Payload($data[self::PAYLOAD_KEY] ?? []);

        return new self($payload, $data);
    }

    /**
     * @param Payload $payload
     * @param mixed[] $data
     */
    public function __construct(Payload $payload, array $data = [])
    {
        $this->payload = $payload;

        // Extract (and unset) all special keys from the data into the internal state.
        $this->isAcknowledged = $data[self::ACKNOWLEDGED_KEY] ?? false;
        $this->isDeferred = $data[self::DEFERRED_KEY] ?? false;
        $this->appId = $data[self::APP_ID_KEY] ?? $this->payload->getAppId();
        foreach (self::SPECIAL_KEYS as $key) {
            unset($data[$key]);
        }

        // Put all other data into the general context array.
        $this->setData($data);
    }

    /**
     * Sets the application config for the context.
     *
     * Several parts of the context rely on objects/data in the AppConfig.
     *
     * @param AppConfig $config
     * @return static
     */
    public function withAppConfig(AppConfig $config): self
    {
        $this->appConfig = $config;
        $this->reconcileAppId();

        // Update the Logger with Context data.
        $this->logger()
            ->addContext($this->payload->getSummary())
            ->debug('Incoming Slack request', [
                'context' => $this->toArray(),
            ]);

        return $this;
    }

    /**
     * Sets the callback function for acks.
     *
     * This is not needed for all server implementations, but if it is, it should be set by the Server implementation,
     * and should not be explicitly provided otherwise.
     *
     * @param callable $callback
     * @return $this
     */
    public function withAckCallback(callable $callback): self
    {
        $this->ackCallback = $callback instanceof Closure ? $callback : Closure::fromCallable($callback);

        return $this;
    }

    /**
     * Sets the client used to "respond" to a Slack message (via the response_url).
     *
     * By default, it uses the PSR-7 HTTP client, and should not need to be explicitly provided.
     *
     * @param RespondClient $respondClient
     * @return $this
     */
    public function withRespondClient(RespondClient $respondClient): self
    {
        $this->respondClient = $respondClient;

        return $this;
    }

    /**
     * Sets the API client used to call Slack's Web API.
     *
     * By default, it uses built-in implementation, and should not need to be explicitly provided.
     *
     * @param ApiClient $apiClient
     * @return $this
     */
    public function withApiClient(ApiClient $apiClient): self
    {
        $this->apiClient = $apiClient;

        return $this;
    }

    /**
     * Sets a value in the context data.
     *
     * Values in the context can be used by any listener or interceptor.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set(string $key, $value): self
    {
        if (in_array($key, self::SPECIAL_KEYS, true)) {
            $specialKeys = implode(', ', self::SPECIAL_KEYS);
            throw new Exception("Cannot modify the following internal keys in the context: {$specialKeys}");
        }

        if ($value === null) {
            unset($this->data[$key]);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function isAcknowledged(): bool
    {
        return $this->isAcknowledged;
    }

    public function isDeferred(): bool
    {
        return $this->isDeferred;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function getAppConfig(): AppConfig
    {
        if (!isset($this->appConfig)) {
            $this->appConfig = new AppConfig();
        }

        return $this->appConfig;
    }

    public function getApiClient(): ApiClient
    {
        if (!isset($this->apiClient)) {
            $tokenStore = $this->getAppConfig()->getTokenStore();
            $this->apiClient = new SimpleApiClient($tokenStore->get(
                $this->payload->getTeamId(),
                $this->payload->getEnterpriseId()
            ));
        }

        return $this->apiClient;
    }

    public function getAck(): ?string
    {
        return $this->ack;
    }

    public function container(): ContainerInterface
    {
        return $this->getAppConfig()->getContainer();
    }

    public function payload(): Payload
    {
        return $this->payload;
    }

    public function logger(): SlackLogger
    {
        return $this->getAppConfig()->getLogger();
    }

    /**
     * Calls a Slack API endpoint with the provided parameters.
     *
     * Slack APIs are all named like "group.operation" (e.g., `chat.postMessage`) and called via POST. Parameters are
     * encoded to either JSON or application/x-www-form-urlencoded format, depending on the operation. All responses are
     * in JSON format and get decoded into an associative array. APIs require certain scopes to be configured for your
     * app in order to be used. For required scopes, parameters, and other details for any operation, you can refer to
     * Slack's API documentation: https://api.slack.com/methods.
     *
     * @param string $api Name of the API (e.g., `chat.postMessage`).
     * @param array<string, mixed> $params Associative array of input parameters.
     * @return array<string, mixed> JSON-decoded output data.
     * @throws Exception If the API call is not successful.
     * @see https://api.slack.com/methods
     */
    public function api(string $api, array $params): array
    {
        return $this->getApiClient()->call($api, $params);
    }

    public function blocks(): Blocks
    {
        if (!isset($this->blocks)) {
            $this->blocks = new Blocks();
        }

        return $this->blocks;
    }

    public function fmt(): Formatter
    {
        return Kit::formatter();
    }

    /**
     * Sends a 200 response as an ack back to Slack, so Slack knows the payload was received.
     *
     * Acks generally have an empty body, but for some payload types, it may be appropriate to send a message (command)
     * or other data (block_suggestion) as part of the ack.
     *
     * @param Message|JsonSerializable|mixed[]|string|callable(): Message|null $ack Message/data to use as the ack body.
     * @throws JsonException if non-null ack cannot be JSON encoded.
     */
    public function ack($ack = null): void
    {
        if ($this->isAcknowledged) {
            throw new Exception('Payload has already been acknowledged');
        }

        if ($ack !== null) {
            // Convert everything that's not encodable to a Message.
            if (!(is_array($ack) || $ack instanceof JsonSerializable)) {
                $ack = Coerce::message($ack);
            }

            $ack = json_encode($ack, JSON_THROW_ON_ERROR);
            $this->logger()->debug('Provided non-empty ack back to Slack', compact('ack'));
        }

        // Record and perform the ack.
        $this->isAcknowledged = true;
        $this->ack = $ack;
        if (isset($this->ackCallback)) {
            ($this->ackCallback)($ack);
        }
    }

    /**
     * Marks the context as deferred, meaning that more processing is needed after the ack.
     *
     * This is typically not called from users' listeners, and only is significant when an asynchronous process is
     * configured to handle deferred contexts. Asynchronous handling requires advanced configurations of PHP or
     * additional infrastructure, and is not supported by any default installations of the framework or PHP. By default,
     * handling deferred contexts happens immediately before the initial ack response, so all context handling should
     * take less than 3 seconds.
     *
     * @param bool $defer
     */
    public function defer(bool $defer = true): void
    {
        $this->isDeferred = $defer;
    }

    /**
     * @param Message|Message[]|string|callable(): Message $message
     * @param string|null $url
     */
    public function respond($message, ?string $url = null): void
    {
        $url ??= $this->payload->getResponseUrl();
        if ($url === null) {
            throw new Exception('Cannot respond: Response URL must be available in the payload or explicitly provided');
        }

        if (!isset($this->respondClient)) {
            $this->respondClient = new SimpleRespondClient();
        }

        $this->respondClient->respond($url, Coerce::message($message));
    }

    /**
     * @param Message|Message[]|string|callable(): Message $message
     * @param string|null $channel
     * @param string|null $threadTs
     */
    public function say($message, ?string $channel = null, ?string $threadTs = null): void
    {
        try {
            $data = Coerce::message($message)->toArray();
            $this->api('chat.postMessage', array_filter([
                'channel' => $channel ?? $this->payload->getChannelId(),
                'blocks' => $data['blocks'] ?? null,
                'attachments' => $data['attachments'] ?? null,
                'text' => $data['text'] ?? null,
                'thread_ts' => $threadTs,
            ]));
        } catch (Throwable $ex) {
            throw new Exception('API call to `chat.postMessage` failed', 0, $ex);
        }
    }

    /**
     * @param OptionList|array<string, string>|null $options'
     *
     * @throws JsonException
     */
    public function options($options): void
    {
        if (!$this->payload->isType(PayloadType::blockSuggestion())) {
            throw new Exception('Can only to use `options()` for block_suggestion requests');
        }

        if (is_array($options)) {
            $options = OptionList::new()->options($options);
        }

        $this->ack($options);
    }

    /**
     * @deprecated Use appHome() and its methods.
     *
     * @param AppHome|AppHome[]|string|callable(): AppHome $appHome
     * @param string|null $userId If null, the value from the current payload will be used.
     * @param bool $useHashIfAvailable Set to false if you want to overwrite the current app home without a hash check.
     * @return mixed[]
     */
    public function home($appHome, ?string $userId = null, bool $useHashIfAvailable = true): array
    {
        try {
            $result = $this->api('views.publish', array_filter([
                'user_id' => $userId ?? $this->payload->getUserId(),
                'view' => Coerce::appHome($appHome)->toArray(),
                'hash' => $useHashIfAvailable ? $this->payload->get('view.hash') : null,
            ]));

            return $result['view'] ?? [];
        } catch (Throwable $ex) {
            throw new Exception('API call to `views.publish` failed', 0, $ex);
        }
    }

    /**
     * Perform an operation on the App Home.
     *
     * @return Home
     */
    public function appHome(): Home
    {
        return new Home($this);
    }

    /**
     * Perform an operation with modals.
     *
     * @return Modals
     */
    public function modals(): Modals
    {
        return new Modals($this);
    }

    /**
     * Ack with a response to the current modal.
     *
     * @return View
     */
    public function view(): View
    {
        if (!$this->payload->isType(PayloadType::viewSubmission())) {
            throw new Exception('Can only to use `view()` (response actions) for view_submission requests');
        }

        return new View($this);
    }

    /**
     * Log and display an error to the user.
     *
     * @param Throwable $exception
     * @return Error
     */
    public function error(Throwable $exception): Error
    {
        return new Error($this, $exception);
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return $this->getData() + [
            self::PAYLOAD_KEY => $this->payload->toArray(),
            self::ACKNOWLEDGED_KEY => $this->isAcknowledged,
            self::DEFERRED_KEY => $this->isDeferred,
            self::APP_ID_KEY => $this->appId,
        ];
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->set($offset, null);
    }

    private function reconcileAppId(): void
    {
        $this->appId ??= $this->appConfig->getId();
        $configAppId = $this->appConfig->getId();

        if (isset($this->appId, $configAppId) && $this->appId !== $configAppId) {
            throw new Exception("App ID mismatch between Context ({$this->appId}) and AppConfig ({$configAppId})");
        }

        if (isset($this->appId)) {
            $this->appConfig->withId($this->appId);
        }
    }
}
