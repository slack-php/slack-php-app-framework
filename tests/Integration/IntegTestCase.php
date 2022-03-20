<?php

namespace SlackPhp\Framework\Tests\Integration;

use SlackPhp\Framework\Context;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use SlackPhp\BlockKit\Surfaces\Message;
use SlackPhp\Framework\Clients\ApiClient;
use SlackPhp\Framework\Clients\RespondClient;
use SlackPhp\Framework\Contexts\DataBag;
use SlackPhp\Framework\Http\HttpServer;
use SlackPhp\Framework\Interceptor;
use SlackPhp\Framework\Interceptors\Tap;
use SlackPhp\Framework\Tests\Fakes\FakeResponseEmitter;

class IntegTestCase extends TestCase
{
    private const SIGNING_KEY = 'abc123';
    private const BOT_TOKEN = 'xoxb-abc123';
    private const HEADER_SIGNATURE = 'X-Slack-Signature';
    private const HEADER_TIMESTAMP = 'X-Slack-Request-Timestamp';

    protected Psr17Factory $httpFactory;
    /** @var LoggerInterface|MockObject */
    protected $logger;
    private FakeResponseEmitter $responseEmitter;

    public function setUp(): void
    {
        putenv('SLACK_SIGNING_KEY=' . self::SIGNING_KEY);
        putenv('SLACK_BOT_TOKEN=' . self::BOT_TOKEN);

        parent::setUp();
        $this->httpFactory = new Psr17Factory();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->responseEmitter = new FakeResponseEmitter();
    }

    protected function parseResponse(?ResponseInterface $response = null): DataBag
    {
        $response = $response ?? $this->responseEmitter->getLastResponse();

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

    /**
     * @phpstan-param string[] $data
     */
    protected function createCommandRequest(array $data, ?int $timestamp = null): ServerRequestInterface
    {
        return $this->createRequest(http_build_query($data), 'application/x-www-form-urlencoded', $timestamp);
    }

    /**
     * @phpstan-param array<string, array<string>|string> $data
     */
    protected function createInteractiveRequest(array $data, ?int $timestamp = null): ServerRequestInterface
    {
        return $this->createRequest(
            http_build_query(['payload' => json_encode($data)]),
            'application/x-www-form-urlencoded',
            $timestamp
        );
    }

    /**
     * @phpstan-param string[] $data
     */
    protected function createEventRequest(array $data, ?int $timestamp = null): ServerRequestInterface
    {
        return $this->createRequest((string)json_encode($data), 'application/json', $timestamp);
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
        return HttpServer::new()
            ->withLogger($this->logger)
            ->withRequest($request)
            ->withResponseEmitter($this->responseEmitter);
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
