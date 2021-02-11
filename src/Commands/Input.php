<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Commands;

use SlackPhp\Framework\Contexts\HasData;

class Input
{
    use HasData;

    private Definition $definition;

    public function __construct(string $commandText, Definition $definition)
    {
        $this->definition = $definition;
        $parser = new Parser($this->definition);
        $this->setData($parser->parse($commandText));
    }

    /**
     * @return Definition
     */
    public function getDefinition(): Definition
    {
        return $this->definition;
    }
}
