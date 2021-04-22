<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Auth;

use SlackPhp\Framework\Env;

/**
 * Contains credentials required for all types of app authentication.
 */
class AppCredentials
{
    /** @var array<string, mixed> */
    private array $customSecrets;

    private ?string $appToken;
    private ?string $clientId;
    private ?string $clientSecret;
    private ?string $defaultBotToken;
    private ?string $signingKey;
    private ?string $stateSecret;

    public static function fromEnv(?string $prefix = null): self
    {
        $env = Env::vars($prefix);
        return new self(
            $env->getSigningKey(),
            $env->getBotToken(),
            $env->getClientId(),
            $env->getClientSecret(),
            $env->getStateSecret(),
            $env->getAppToken()
        );
    }

    public static function new(): self
    {
        return new self();
    }

    /**
     * @param string|null $signingKey
     * @param string|null $defaultBotToken
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @param string|null $stateSecret
     * @param string|null $appToken
     * @param array<string, mixed> $customSecrets
     */
    public function __construct(
        ?string $signingKey = null,
        ?string $defaultBotToken = null,
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $stateSecret = null,
        ?string $appToken = null,
        array $customSecrets = []
    ) {
        $this->signingKey = $signingKey;
        $this->defaultBotToken = $defaultBotToken;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->stateSecret = $stateSecret;
        $this->appToken = $appToken;
        $this->customSecrets = $customSecrets;
    }

    /**
     * @param string $appToken
     * @return $this
     */
    public function withAppToken(string $appToken): self
    {
        $this->appToken = $appToken;

        return $this;
    }

    /**
     * @param string $clientId
     * @return $this
     */
    public function withClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @param string $clientSecret
     * @return $this
     */
    public function withClientSecret(string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    /**
     * @param array<string, mixed> $customSecrets
     * @return $this
     */
    public function withCustomSecrets(array $customSecrets): self
    {
        $this->customSecrets = $customSecrets;

        return $this;
    }

    /**
     * @param string $defaultBotToken
     * @return $this
     */
    public function withDefaultBotToken(string $defaultBotToken): self
    {
        $this->defaultBotToken = $defaultBotToken;

        return $this;
    }

    /**
     * @param string $signingKey
     * @return $this
     */
    public function withSigningKey(string $signingKey): self
    {
        $this->signingKey = $signingKey;

        return $this;
    }

    /**
     * @param string $stateSecret
     * @return $this
     */
    public function withStateSecret(string $stateSecret): self
    {
        $this->stateSecret = $stateSecret;

        return $this;
    }

    public function supportsHttpAuth(): bool
    {
        return isset($this->signingKey);
    }

    public function supportsSocketAuth(): bool
    {
        return isset($this->appToken);
    }

    public function supportsApiAuth(): bool
    {
        return isset($this->defaultBotToken);
    }

    public function supportsInstallAuth(): bool
    {
        return isset($this->clientId, $this->clientSecret);
    }

    public function supportsAnyAuth(): bool
    {
        return $this->supportsHttpAuth()
            || $this->supportsApiAuth()
            || $this->supportsInstallAuth()
            || $this->supportsSocketAuth();
    }

    public function getAppToken(): ?string
    {
        return $this->appToken;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomSecrets(): array
    {
        return $this->customSecrets;
    }

    public function getDefaultBotToken(): ?string
    {
        return $this->defaultBotToken;
    }

    public function getSigningKey(): ?string
    {
        return $this->signingKey;
    }

    public function getStateSecret(): ?string
    {
        return $this->stateSecret;
    }
}
