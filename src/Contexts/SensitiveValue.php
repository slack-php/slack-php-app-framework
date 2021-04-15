<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

use JsonSerializable;

class SensitiveValue implements JsonSerializable
{
    /** @var mixed */
    private $rawValue;

    /**
     * @param mixed $rawValue
     */
    public function __construct($rawValue)
    {
        $this->rawValue = $rawValue;
    }

    /**
     * @return mixed
     */
    public function getRawValue()
    {
        return $this->rawValue;
    }

    public function jsonSerialize()
    {
        return null;
    }
}
