<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Commands;

use SlackPhp\BlockKit\Surfaces\Message;

class Definition
{
    private string $name;
    private ?string $subCommand;
    private string $description;

    /** @var ArgDefinition[] */
    private array $args = [];

    /** @var OptDefinition[] */
    private array $opts = [];

    /**
     * @param ArgDefinition[] $args
     * @param OptDefinition[] $opts
     */
    public function __construct(
        string $name,
        ?string $subCommand,
        string $description,
        array $args = [],
        array $opts = []
    ) {
        $this->name = $name;
        $this->subCommand = $subCommand;
        $this->description = $description;
        $this->args = $args;
        $this->opts = $opts;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getSubCommand(): ?string
    {
        return $this->subCommand;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return ArgDefinition[]
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return OptDefinition[]
     */
    public function getOpts(): array
    {
        return $this->opts;
    }

    public function getHelpMessage(?string $error = null): Message
    {
        return Message::new()->ephemeral()->tap(function (Message $msg) use ($error) {
            if ($error) {
                $msg->header(':warning: Command Error')->text("> {$error}");
            }

            $msg->text("*Command Usage*: ```{$this->getCommandFormat()}```");
        });
    }

    public function getCommandFormat(): string
    {
        $parts = [];

        $parts[] = "/{$this->name}";

        if ($this->subCommand !== null) {
            $parts[] = $this->subCommand;
        }

        foreach ($this->args as $arg) {
            $parts[] = $arg->getFormat();
        }

        $opts = '';
        foreach ($this->opts as $opt) {
            $opts .= "\n  {$opt->getFormat()}";
        }

        return implode(' ', $parts) . $opts;
    }
}
