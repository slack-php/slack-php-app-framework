<?php

use SlackPhp\Framework\{App, Context};

return App::new()->any(fn (Context $ctx) => $ctx->ack('hello'));
