<?php

namespace SlackPhp\Framework\Tests\Integration;

use PHPUnit\Framework\TestCase;
use SlackPhp\BlockKit\Surfaces\Message;
use SlackPhp\Framework\App;
use SlackPhp\Framework\Clients\RespondClient;
use SlackPhp\Framework\Context;
use SlackPhp\Framework\Deferral\DeferredContextCliServer;

class DeferredContextCliServerTest extends TestCase
{
    public function testCanProcessDeferredContext(): void
    {
        $serializedContext = base64_encode((string)json_encode([
            '_acknowledged' => true,
            '_deferred' => true,
            '_payload' => [
                'command' => '/foo',
                'response_url' => 'https://example.org',
            ],
        ]));

        $respondClient = $this->createMock(RespondClient::class);
        $respondClient->expects($this->once())
            ->method('respond')
            ->with(
                'https://example.org',
                $this->callback(fn ($v): bool => $v instanceof Message && strpos($v->toJson(), 'bar') !== false)
            );

        $app = App::new()
            ->commandAsync('foo', fn (Context $ctx) => $ctx->respond('bar'))
            ->tap(function (Context $ctx) use ($respondClient) {
                $ctx->withRespondClient($respondClient);
            });
        DeferredContextCliServer::new()
            ->withApp($app)
            ->withArgs(['script', $serializedContext, '--soft-exit'])
            ->start();
    }
}
