<?php

namespace SlackPhp\Framework\Tests\Integration;

use SlackPhp\Framework\Context;
use SlackPhp\Framework\Clients\ApiClient;
use SlackPhp\Framework\Clients\RespondClient;
use SlackPhp\Framework\Contexts\DataBag;
use SlackPhp\Framework\Http\HttpServer;
use SlackPhp\Framework\Http\ResponseEmitter;
use SlackPhp\Framework\Interceptor;
use SlackPhp\Framework\Interceptors\Tap;
use Jeremeamia\Slack\BlockKit\Surfaces\Message;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class IntegTestCase extends TestCase
{
    private const SIGNING_KEY = 'abc123';
    private const BOT_TOKEN = 'xoxb-abc123';
    private const HEADER_SIGNATURE = 'X-Slack-Signature';
    private const HEADER_TIMESTAMP = 'X-Slack-Request-Timestamp';

    /** @var Psr17Factory */
    protected $httpFactory;

    /** @var LoggerInterface|MockObject */
    protected $logger;

    /** @var ResponseInterface|null */
    protected $lastResponse;

    public function setUp(): void
    {
        putenv('SLACK_SIGNING_KEY=' . self::SIGNING_KEY);
        putenv('SLACK_BOT_TOKEN=' . self::BOT_TOKEN);

        parent::setUp();
        $this->httpFactory = new Psr17Factory();
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    protected function parseResponse(?ResponseInterface $response = null): DataBag
    {
        $response = $response ?? $this->getLastResponse();

        $content = (string) $response->getBody();
        if ($content === '') {
            return new DataBag(['ack' => true]);
        }

        try {
            return new DataBag(\json_decode($content, true, 512, \JSON_THROW_ON_ERROR));
        } catch (\JsonException $exception) {
            $this->fail('Could not parse response JSON: ' . $exception->getMessage());
        }
    }

    protected function getLastResponse(): ResponseInterface
    {
        if ($this->lastResponse) {
            $response = $this->lastResponse;
            $this->lastResponse = null;

            return $response;
        }

        $this->fail('There was no last response');
    }

    protected function createCommandRequest(array $data, ?int $timestamp = null): ServerRequestInterface
    {
        return $this->createRequest(http_build_query($data), 'application/x-www-form-urlencoded', $timestamp);
    }

    protected function createInteractiveRequest(array $data, ?int $timestamp = null): ServerRequestInterface
    {
        return $this->createRequest(
            http_build_query(['payload' => json_encode($data)]),
            'application/x-www-form-urlencoded',
            $timestamp
        );
    }

    protected function createEventRequest(array $data, ?int $timestamp = null): ServerRequestInterface
    {
        return $this->createRequest(json_encode($data), 'application/json', $timestamp);
    }

    private function createRequest(string $content, string $contentType, ?int $timestamp = null): ServerRequestInterface
    {
        // Create signature
        $timestamp = $timestamp ?? time();
        $stringToSign = sprintf('v0:%d:%s', $timestamp, $content);
        $signature = 'v0=' . hash_hmac('sha256', $stringToSign, self::SIGNING_KEY);

        return $this->httpFactory->createServerRequest('POST', '/')
            ->withHeader(self::HEADER_TIMESTAMP, (string) $timestamp)
            ->withHeader(self::HEADER_SIGNATURE, $signature)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Length', (string) strlen($content))
            ->withBody($this->httpFactory->createStream($content));
    }

    protected function createHttpServer(ServerRequestInterface $request): HttpServer
    {
        $setLastResponse = function (ResponseInterface $response): void {
            $this->lastResponse = $response;
        };

        $emitter = new class($setLastResponse) implements ResponseEmitter {
            /** @var callable */
            private $fn;

            public function __construct(callable $fn)
            {
                $this->fn = $fn;
            }

            public function emit(ResponseInterface $response): void
            {
                ($this->fn)($response);
            }
        };

        return HttpServer::new()
            ->withLogger($this->logger)
            ->withRequest($request)
            ->withResponseEmitter($emitter);
    }

    protected function failOnLoggedErrors(): void
    {
        $this->logger->method('error')->willReturnCallback(function (string $message, array $context) {
            $message = "Logged an error: {$message}\nContext:\n";
            foreach ($context as $key => $value) {
                $message .= "- {$key}: {$value}\n";
            }

            $this->fail($message);
        });
    }

    /**
     * @param mixed $result
     */
    protected function assertIsAck($result): void
    {
        if (!$result instanceof DataBag) {
            $this->fail('Tried to assertIsAck on invalid value');
        }

        if ($result->get('ack') !== true) {
            $this->fail('Result was not an "ack"');
        }
    }

    protected function interceptApiCall(string $api, callable $handler): Interceptor
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->expects($this->once())
            ->method('call')
            ->with($api, $this->anything())
            ->willReturnCallback(function (string $api, array $params) use ($handler) {
                return $handler($params);
            });

        return new Tap(function (Context $context) use ($apiClient) {
            $context->withApiClient($apiClient);
        });
    }

    protected function interceptRespond(string $responseUrl): Interceptor
    {
        $respondClient = $this->createMock(RespondClient::class);
        $respondClient->expects($this->once())
            ->method('respond')
            ->with($responseUrl, $this->isInstanceOf(Message::class));

        return new Tap(function (Context $context) use ($respondClient) {
            $context->withRespondClient($respondClient);
        });
    }
}
