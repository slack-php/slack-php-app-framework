<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Commands;

use SlackPhp\Framework\Exception;

class DefinitionBuilder
{
    private ?string $name;
    private ?string $subCommand;
    private string $description = '';

    /** @var ArgDefinition[] */
    private array $args = [];

    /** @var OptDefinition[] */
    private array $opts = [];

    public static function new(): self
    {
        return new self();
    }

    public function name(string $commandName): self
    {
        $this->name = $commandName;

        return $this;
    }

    public function subCommand(string $subCommandName): self
    {
        $this->subCommand = $subCommandName;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function arg(
        string $name,
        string $type = ArgDefinition::TYPE_STRING,
        bool $required = true,
        string $description = ''
    ): self {
        $this->args[] = new ArgDefinition($name, $type, $required, $description);

        return $this;
    }

    public function opt(
        string $name,
        string $type = OptDefinition::TYPE_BOOL,
        ?string $shortName = null,
        string $description = ''
    ): self {
        $this->opts[] = new OptDefinition($name, $type, $shortName, $description);

        return $this;
    }

    public function build(): Definition
    {
        if ($this->name === null) {
            throw new Exception('Cannot build command without name');
        }

        return new Definition($this->name, $this->subCommand, $this->description, $this->args, $this->opts);
    }
}
