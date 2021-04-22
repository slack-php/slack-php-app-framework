<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Http;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use SlackPhp\Framework\{Application, Context, Deferrer};
use SlackPhp\Framework\Deferral\PreAckDeferrer;
use Nyholm\Psr7\Response;
use Psr\Http\Server\RequestHandlerInterface as HandlerInterface;

class AppHandler implements HandlerInterface
{
    private ?Deferrer $deferrer;
    private Application $app;

    /**
     * @param Application $app
     * @param Deferrer|null $deferrer
     */
    public function __construct(Application $app, ?Deferrer $deferrer = null)
    {
        $this->app = $app;
        $this->deferrer = $deferrer ?? new PreAckDeferrer($app);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Prepare the app context for the listener(s).
        $context = Util::createContextFromRequest($request);

        // Delegate to the listener(s) for handling the app context.
        $this->app->handle($context);
        if ($context->isDeferred()) {
            $this->deferrer->defer($context);
        }

        return $this->createResponseFromContext($context);
    }

    public function createResponseFromContext(Context $context): ResponseInterface
    {
        if (!$context->isAcknowledged()) {
            throw new HttpException('No ack provided by the app');
        }

        $ack = $context->getAck();
        if ($ack === null) {
            return new Response(200);
        }

        return new Response(200, [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($ack),
        ], $ack);
    }
}
