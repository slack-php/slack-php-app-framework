<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

use function getenv;
use function strtoupper;

final class Env
{
    private const DEFAULT_PREFIX = 'SLACK';
    private const APP_TOKEN = 'APP_TOKEN';
    private const APP_ID = 'APP_ID';
    private const BOT_TOKEN = 'BOT_TOKEN';
    private const CLIENT_ID = 'CLIENT_ID';
    private const CLIENT_SECRET = 'CLIENT_SECRET';
    private const FIVE_MINUTES = 60 * 5;
    private const MAX_CLOCK_SKEW = 'MAX_CLOCK_SKEW';
    private const SCOPES = 'SCOPES';
    private const SIGNING_KEY = 'SIGNING_KEY';
    private const SKIP_AUTH = 'SKIP_AUTH';
    private const STATE_SECRET = 'STATE_SECRET';

    /** @var array<string, self> */
    private static array $instances = [];

    private string $prefix;

    /**
     * Returns an instance of Env for the given prefix (default: SLACK)
     *
     * @param string|null $prefix
     * @return static
     */
    public static function vars(?string $prefix = null): self
    {
        $prefix = strtoupper($prefix ?? self::DEFAULT_PREFIX);

        return self::$instances[$prefix] ??= new self($prefix);
    }

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Gets the "app token" from the environment, which the app uses to establish a connection in Socket Mode.
     *
     * @return string|null
     */
    public function getAppToken(): ?string
    {
        return $this->get(self::APP_TOKEN);
    }

    /**
     * Gets the app ID from the environment, which the app uses to identify itself.
     *
     * @return string|null
     */
    public function getAppId(): ?string
    {
        return $this->get(self::APP_ID);
    }

    /**
     * Gets the "bot token" from the environment, which the app uses to call Slack APIs for the default workspace.
     *
     * @return string|null
     */
    public function getBotToken(): ?string
    {
        return $this->get(self::BOT_TOKEN);
    }

    /**
     * Gets the client ID from the environment, which the app uses in the OAuth flow when installed to a workspace.
     *
     * @return string|null
     */
    public function getClientId(): ?string
    {
        return $this->get(self::CLIENT_ID);
    }

    /**
     * Gets the client secret from the environment, which the app uses in the OAuth flow when installed to a workspace.
     *
     * @return string|null
     */
    public function getClientSecret(): ?string
    {
        return $this->get(self::CLIENT_SECRET);
    }

    /**
     * Gets the scopes from the environment, which the app uses in the OAuth flow when installed to a workspace.
     *
     * @return string[]
     */
    public function getScopes(): array
    {
        $value = $this->get(self::SCOPES);

        return $value ? explode(',', $value) : [];
    }

    /**
     * Gets the signing key from the environment, which the app uses to validate incoming requests.
     *
     * @return string|null
     */
    public function getSigningKey(): ?string
    {
        return $this->get(self::SIGNING_KEY);
    }

    /**
     * Gets the state secret from the environment, which the app uses in the OAuth flow when installed to a workspace.
     *
     * @return string|null
     */
    public function getStateSecret(): ?string
    {
        return $this->get(self::STATE_SECRET);
    }

    /**
     * Gets an environment variable value by its name.
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        $key = "{$this->prefix}_{$key}";

        return getenv($key, true) ?: getenv($key) ?: null;
    }

    /**
     * Gets the maximum allowed clock skew from the environment, which the app uses to validate incoming requests.
     *
     * @return int
     */
    public static function getMaxClockSkew(): int
    {
        $value = self::vars('SLACKPHP')->get(self::MAX_CLOCK_SKEW);

        return $value ? (int) $value : self::FIVE_MINUTES;
    }

    /**
     * Gets the skip auth flag from the environment, which the app uses to determine whether to bypass authentication.
     *
     * @return bool
     */
    public static function getSkipAuth(): bool
    {
        return (bool) self::vars('SLACKPHP')->get(self::SKIP_AUTH);
    }
}
