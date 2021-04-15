<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

interface Listener
{
    public function handle(Context $context): void;
}
