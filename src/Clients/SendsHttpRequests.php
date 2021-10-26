<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Clients;

use JsonException;
use SlackPhp\Framework\Exception;
use Throwable;

use function file_get_contents;
use function json_encode;
use function json_decode;
use function stream_context_create;
use function strlen;

trait SendsHttpRequests
{
    private static array $baseOptions = [
        'ignore_errors' => true,
        'protocol_version' => 1.1,
        'timeout' => 5,
        'user_agent' => 'slack-php/slack-app-framework',
    ];

    private static array $errorMessages = [
        'network' => 'Slack API request could not be completed',
        'unexpected' => 'Slack API request experienced an unexpected error: %s',
        'unsuccessful' => 'Slack API response was unsuccessful: %s',
        'json_decode' => 'Slack API response contained invalid JSON: %s',
        'json_encode' => 'Slack API request content contained invalid JSON: %s',
    ];

    private function sendJsonRequest(string $method, string $url, array $input): array
    {
        $header = '';
        if (isset($input['token'])) {
            $header .= "Authorization: Bearer {$input['token']}\r\n";
            unset($input['token']);
        }

        try {
            $content = json_encode($input, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonErr) {
            throw new Exception(sprintf(self::$errorMessages['json_encode'], $jsonErr->getMessage()), 0, $jsonErr);
        }

        $length = strlen($content);
        $header .= "Content-Type: application/json\r\nContent-Length: {$length}\r\n";

        return $this->sendHttpRequest($method, $url, $header, $content);
    }

    private function sendFormRequest(string $method, string $url, array $input): array
    {
        $content = http_build_query($input);
        $length = strlen($content);
        $header = "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: {$length}\r\n";

        return $this->sendHttpRequest($method, $url, $header, $content);
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $header
     * @param string $content
     * @return array<string, mixed>
     */
    private function sendHttpRequest(string $method, string $url, string $header, string $content): array
    {
        $errorContext = compact('method', 'url');

        try {
            $httpOptions = self::$baseOptions + compact('method', 'header', 'content');
            $responseBody = file_get_contents($url, false, stream_context_create(['http' => $httpOptions]));
            $responseHeader = $http_response_header ?? [];
            $errorContext += $responseHeader;

            if (empty($responseBody) || empty($responseHeader)) {
                throw (new Exception(self::$errorMessages['network']))->addContext($errorContext);
            } elseif ($responseBody === 'ok') {
                return ['ok' => true];
            }

            $data = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
            if (isset($data['ok']) && $data['ok'] === true) {
                return $data;
            } else {
                throw (new Exception(sprintf(self::$errorMessages['unsuccessful'], $data['error'] ?? 'Unknown')))
                    ->addContext($errorContext);
            }
        } catch (Exception $frameworkErr) {
            throw $frameworkErr;
        } catch (JsonException $jsonErr) {
            throw (new Exception(sprintf(self::$errorMessages['json_decode'], $jsonErr->getMessage()), 0, $jsonErr))
                ->addContext($errorContext);
        } catch (Throwable $otherErr) {
            throw (new Exception(sprintf(self::$errorMessages['unexpected'], $otherErr->getMessage()), 0, $otherErr))
                ->addContext($errorContext);
        }
    }
}
