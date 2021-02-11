<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Commands;

use SlackPhp\Framework\Exception;

class ParsingException extends Exception
{
    public function __construct($message, array $values = [])
    {
        parent::__construct(vsprintf($message, $values));
    }
}
