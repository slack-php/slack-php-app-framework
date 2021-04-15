<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

use JsonSerializable;

class DataBag implements JsonSerializable
{
    use HasData;
}
