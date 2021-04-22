<?php

namespace SlackPhp\Framework\Tests\Integration\Apps;

use SlackPhp\Framework\{BaseApp, Context, Router};

class AnyApp extends BaseApp
{
    protected function prepareRouter(Router $router): void
    {
        $router->any(fn (Context $ctx) => $ctx->ack('hello'));
    }
}

