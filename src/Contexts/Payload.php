<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

use JsonException;
use JsonSerializable;
use UnexpectedValueException;

use function json_decode;
use function urldecode;
use function is_string;
use function parse_str;

class Payload implements JsonSerializable
{
    use HasData;

    private PayloadType $type;

    /**
     * @param string $body
     * @param string $contentType
     * @return self
     * @throws JsonException
     * @throws UnexpectedValueException
     */
    public static function fromHttpRequest(string $body, string $contentType): self
    {
        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                parse_str($body, $data);
                break;
            case 'application/json':
                $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                break;
            default:
                throw new UnexpectedValueException('Unsupported request body format');
        }

        // Parse payload field if it's in JSON format.
        if (isset($data['payload']) && is_string($data['payload'])) {
            $data['payload'] = json_decode(urldecode($data['payload']), true, 512, JSON_THROW_ON_ERROR);
        }

        return new Payload($data['payload'] ?? $data);
    }

    /**
     * @param string[] $data
     */
    public function __construct(array $data = [])
    {
        if (isset($data['type'])) {
            $this->type = PayloadType::withValue($data['type']);
        } elseif (isset($data['command'])) {
            $this->type = PayloadType::command();
        } else {
            $this->type = PayloadType::unknown();
        }

        $this->setData($data);
    }

    public function getType(): PayloadType
    {
        return $this->type;
    }

    public function isType(PayloadType $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Returns the main ID/name/type for the payload type used for route indexing.
     *
     * @return string|null
     */
    public function getTypeId(): ?string
    {
        $field = $this->type->idField();
        $id = $field ? $this->get($field) : null;
        if ($id !== null) {
            $id = ltrim($id, '/');
        }

        return $id;
    }

    /**
     * Returns the api_api_id property of the payload, common to almost all payload types.
     *
     * @return string|null
     */
    public function getAppId(): ?string
    {
        return $this->get('api_app_id');
    }

    /**
     * Get the enterprise ID for the payload.
     *
     * @return string|null
     */
    public function getEnterpriseId(): ?string
    {
        return $this->getOneOf([
            'authorizations.0.enterprise_id',
            'enterprise.id',
            'enterprise_id',
            'team.enterprise_id',
            'event.enterprise',
            'event.enterprise_id',
        ]);
    }

    /**
     * Get the team/workspace ID for the payload.
     *
     * @return string|null
     */
    public function getTeamId(): ?string
    {
        return $this->getOneOf(['authorizations.0.team_id', 'team.id', 'team_id', 'event.team', 'user.team_id']);
    }

    /**
     * Get the channel ID for the payload.
     *
     * @return string|null
     */
    public function getChannelId(): ?string
    {
        return $this->getOneOf(['channel.id', 'channel_id', 'event.channel', 'event.item.channel']);
    }

    /**
     * Get the user ID for the payload.
     *
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->getOneOf(['user.id', 'user_id', 'event.user']);
    }

    /**
     * Check if the payload is from and enterprise installation.
     *
     * @return bool
     */
    public function isEnterpriseInstall(): bool
    {
        $value = $this->getOneOf(['authorizations.0.is_enterprise_install', 'is_enterprise_install']);

        return $value === true || $value === 'true';
    }

    /**
     * Get the submitted state from the payload, if present.
     *
     * Can be present for view_submission and some view_closed and block_action requests.
     *
     * @return DataBag
     */
    public function getState(): DataBag
    {
        return new DataBag($this->getOneOf(['view.state.values', 'state.values']) ?? []);
    }

    /**
     * Get the private metadata from the payload, if present.
     *
     * Can be present for view_submission and some view_closed requests.
     *
     * @return PrivateMetadata
     */
    public function getMetadata(): PrivateMetadata
    {
        $data = $this->getOneOf(['view.private_metadata', 'event.view.private_metadata']);
        if ($data === null) {
            return new PrivateMetadata();
        }

        return PrivateMetadata::decode($data);
    }

    /**
     * Get the response URL from the payload, if present.
     *
     * Is present for anything that has a conversation context.
     *
     * @return string|null
     */
    public function getResponseUrl(): ?string
    {
        $responseUrl = $this->getOneOf(['response_url', 'response_urls.0.response_url'])
            ?? $this->getMetadata()->get('response_url');

        return $responseUrl === null ? null : (string) $responseUrl;
    }

    /**
     * Gets indentifying information about the payload for the purposes of logging/debugging.
     *
     * @return array<string, string|null>
     */
    public function getSummary(): array
    {
        return [
            'payload_type' => $this->getType()->value(),
            'payload_id_field' => $this->getType()->idField(),
            'payload_id_value' => $this->getTypeId(),
        ];
    }
}
