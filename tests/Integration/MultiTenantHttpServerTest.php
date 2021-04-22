<?php

namespace SlackPhp\Framework\Tests\Integration;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use SlackPhp\Framework\Http\MultiTenantHttpServer;
use SlackPhp\Framework\Tests\Fakes\FakeResponseEmitter;

class MultiTenantHttpServerTest extends TestCase
{
    private ServerRequestInterface $request;
    private FakeResponseEmitter $responseEmitter;
    private MultiTenantHttpServer $server;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('SLACKPHP_SKIP_AUTH=1');
        $this->request = new ServerRequest('POST', '/', ['Content-Type' => 'application/json'], '{}');
        $this->responseEmitter = new FakeResponseEmitter();
        $this->server = MultiTenantHttpServer::new()
            ->registerApp('A1', Apps\AnyApp::class)
            ->registerApp('A2', __DIR__ . '/Apps/any-app.php')
            ->registerApp('A3', fn () => new Apps\AnyApp())
            ->withResponseEmitter($this->responseEmitter);
    }

    protected function tearDown(): void
    {
        putenv('SLACKPHP_SKIP_AUTH=');
        parent::tearDown();
    }

    public function testCanRunAppFromClassName(): void
    {
        $this->server->withRequest($this->request->withQueryParams(['_app' => 'A1']))->start();
        $this->assertArrayHasKey('blocks', $this->responseEmitter->getLastResponseData());
    }

    public function testCanRunAppFromInclude(): void
    {
        $this->server->withRequest($this->request->withQueryParams(['_app' => 'A2']))->start();
        $this->assertArrayHasKey('blocks', $this->responseEmitter->getLastResponseData());
    }

    public function testCanRunAppFromCallback(): void
    {
        $this->server->withRequest($this->request->withQueryParams(['_app' => 'A3']))->start();
        $this->assertArrayHasKey('blocks', $this->responseEmitter->getLastResponseData());
    }
}
