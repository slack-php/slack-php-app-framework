<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Commands;

use SlackPhp\Framework\{Context, Listener};

abstract class CommandListener implements Listener
{
    /** @var array<string, Definition> */
    private static array $definitions = [];

    abstract protected static function buildDefinition(DefinitionBuilder $builder): DefinitionBuilder;

    public static function getDefinition(): Definition
    {
        if (!isset(self::$definitions[static::class])) {
            self::$definitions[static::class] = static::buildDefinition(new DefinitionBuilder())->build();
        }

        return self::$definitions[static::class];
    }

    abstract protected function listenToCommand(Context $context, Input $input): void;

    public function handle(Context $context): void
    {
        $definition = $this->getDefinition();

        try {
            $input = new Input($context->payload()->get('text'), $definition);
            $this->listenToCommand($context, $input);
        } catch (ParsingException $ex) {
            $message = $definition->getHelpMessage($ex->getMessage());
            if ($context->isAcknowledged()) {
                $context->respond($message);
            } else {
                $context->ack($message);
            }
        }
    }
}
