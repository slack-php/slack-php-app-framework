<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Clients;

use SlackPhp\Framework\Exception;

interface ApiClient
{
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
     * @return array<string, mixed> JSON-decoded response data.
     * @throws Exception If the API call is not successful.
     * @see https://api.slack.com/methods
     */
    public function call(string $api, array $params): array;
}
