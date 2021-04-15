<?php

namespace SlackPhp\Framework\Tests\Integration;

use SlackPhp\BlockKit\Surfaces\Modal;
use SlackPhp\Framework\App;
use SlackPhp\Framework\Context;

class ShortcutTest extends IntegTestCase
{
    public function testCanHandleGlobalShortcutRequest(): void
    {
        $this->failOnLoggedErrors();

        $request = $this->createInteractiveRequest([
            'type' => 'shortcut',
            'callback_id' => 'foobar',
            'trigger_id' => 'abc123',
        ]);

        $listener = function (Context $ctx) {
            $view = $ctx->modals()->open('MOCK');
            $this->assertEquals('xyz123', $view['id']);
        };

        $apiMock = function (array $input): array {
            $this->assertEquals('abc123', $input['trigger_id']);
            $this->assertInstanceOf(Modal::class, Modal::fromArray($input['view']));

            return [
                'ok' => true,
                'view' => ['id' => 'xyz123']
            ];
        };

        App::new()
            ->globalShortcut('foobar', $listener)
            ->use($this->interceptApiCall('views.open', $apiMock))
            ->run($this->createHttpServer($request));

        $this->assertIsAck($this->parseResponse());
    }

    public function testCanHandleMessageShortcutRequest(): void
    {
        $this->failOnLoggedErrors();

        $responseUrl = 'https://hooks.slack.com/abc123';
        $request = $this->createInteractiveRequest([
            'type' => 'message_action',
            'callback_id' => 'foobar',
            'response_url' => $responseUrl,
            'message' => ['text' => 'foo']
        ]);

        $listener = function (Context $ctx) {
            $text = $ctx->payload()->get('message.text');
            $this->assertEquals('foo', $text);
            $ctx->respond('bar');
        };

        App::new()
            ->messageShortcut('foobar', $listener)
            ->use($this->interceptRespond($responseUrl))
            ->run($this->createHttpServer($request));

        $this->assertIsAck($this->parseResponse());
    }
}
