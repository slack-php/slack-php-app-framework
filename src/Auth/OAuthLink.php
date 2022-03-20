<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Auth;

use SlackPhp\Framework\Exception;

class OAuthLink
{
    /** @var string */
    private string $clientId;

    /** @var string[] */
    private array $scopes;

    /** @var string[] */
    private array $userScopes;

    /** @var string */
    private string $state;

    /** @var string */
    private $redirectUri;

    public static function new(): self
    {
        return new self();
    }

    public function __construct(?string $clientId = null)
    {
        $this->clientId = $clientId;
    }

    public function withClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @param string[] $scopes
     */
    public function withScopes(array $scopes): self
    {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * @param string[] $userScopes
     */
    public function withUserScopes(array $userScopes): self
    {
        $this->userScopes = $userScopes;

        return $this;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function setRedirectUri(string $redirectUri): self
    {
        $this->redirectUri = $redirectUri;

        return $this;
    }

    public function createUrl(): string
    {
        if (!isset($this->clientId)) {
            throw new Exception('Must provide client ID');
        }

        if (empty($this->scopes) && empty($this->userScopes)) {
            throw new Exception('Must provide scopes and/or user scopes');
        }

        $query = http_build_query(array_filter([
            'scope' => implode(',', $this->scopes),
            'user_scope' => implode(',', $this->userScopes),
            'client_id' => $this->clientId,
            'state' => $this->state,
            'redirect_uri' => $this->redirectUri,
        ]));

        return 'https://slack.com/oauth/v2/authorize?' . $query;
    }

    public function createLink(): string
    {
        return <<<HTML
        <a href="{$this->createUrl()}"><img alt="Add to Slack" height="40" width="139"
            src="https://platform.slack-edge.com/img/add_to_slack.png"
            srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x"
        /></a>
        HTML;
    }
}
