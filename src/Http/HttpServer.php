<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Http;

use SlackPhp\Framework\{Deferrer, AppServer};
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface as HandlerInterface;
use Throwable;

class HttpServer extends AppServer
{
    private ?Deferrer $deferrer;
    private ?ResponseEmitter $emitter;
    private ?ServerRequestInterface $request;

    /**
     * @param Deferrer $deferrer
     * @return $this
     */
    public function withDeferrer(Deferrer $deferrer): self
    {
        $this->deferrer = $deferrer;

        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     * @return $this
     */
    public function withRequest(ServerRequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param ResponseEmitter $emitter
     * @return $this
     */
    public function withResponseEmitter(ResponseEmitter $emitter): self
    {
        $this->emitter = $emitter;

        return $this;
    }

    /**
     * Starts receiving and processing requests from Slack.
     */
    public function start(): void
    {
        try {
            $request = $this->getRequest();
            $response = $this->getHandler()->handle($request);
        } catch (Throwable $exception) {
            $response = new Response($exception->getCode() ?: 500);
            $this->getLogger()->error('Error responding to incoming Slack request', compact('exception'));
        }

        $this->emitResponse($response);
    }

    /**
     * Gets a representation of the request data from super globals.
     *
     * @return ServerRequestInterface
     */
    protected function getRequest(): ServerRequestInterface
    {
        if (!isset($this->request)) {
            try {
                $httpFactory = new Psr17Factory();
                $requestFactory = new ServerRequestCreator($httpFactory, $httpFactory, $httpFactory, $httpFactory);
                $this->request = $requestFactory->fromGlobals();
            } catch (Throwable $ex) {
                throw new HttpException('Invalid Slack request', 400, $ex);
            }
        }

        return $this->request;
    }

    protected function emitResponse(ResponseInterface $response): void
    {
        $emitter = $this->emitter ?? new EchoResponseEmitter();
        $emitter->emit($response);
    }

    /**
     * Gets a request handler for the Slack app.
     *
     * @return HandlerInterface
     */
    protected function getHandler(): HandlerInterface
    {
        $handler = new AppHandler($this->getApp(), $this->deferrer ?? null);

        return Util::applyMiddleware($handler, [new AuthMiddleware($this->getAppCredentials())]);
    }
}
