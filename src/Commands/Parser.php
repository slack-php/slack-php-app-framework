<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Commands;

use function preg_match;
use function stripcslashes;
use function strlen;
use function str_replace;
use function substr;

class Parser
{
    private const NORMAL_STRING = '([^\s]+?)(?:\s|(?<!\\\\)"|(?<!\\\\)\'|$)';
    private const QUOTED_STRING = '(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')';
    //private const REGEX_OPT = '/^--?(?P<key>[^=]+)(?:=(?P<val>.+))?$/';

    private Definition $definition;

    /** @var ArgDefinition[] */
    private array $remainingArgs;

    /** @var OptDefinition[] */
    private array $optMap;

    public function __construct(Definition $definition)
    {
        $this->definition = $definition;
        $this->remainingArgs = $this->definition->getArgs();
        $this->optMap = [];
        foreach ($this->definition->getOpts() as $opt) {
            $this->optMap[$opt->getName()] = $opt;
            if ($opt->getShortName()) {
                $this->optMap[$opt->getShortName()] = $opt;
            }
        }
    }

    /**
     * @param string $input
     * @return mixed[]
     * @throws ParsingException if command is invalid.
     */
    public function parse(string $input): array
    {
        $subCommand = $this->definition->getSubCommand();
        if ($subCommand !== null) {
            if (strpos($input, $subCommand) === 0) {
                $input = ltrim(substr($input, strlen($subCommand)));
            } else {
                throw new ParsingException('Input does not match the defined sub-command: `%s`', [$subCommand]);
            }
        }

        $data = [];
        foreach ($this->getArgsAndOpts($input) as [$key, $value]) {
            $data[$key] = is_array($value)
                ? (isset($data[$key]) ? array_merge($data[$key], $value) : $value)
                : $value;
        }

        foreach ($this->remainingArgs as $arg) {
            if ($arg->isRequired()) {
                throw new ParsingException('Missing required arg: `%s`', [$arg->getName()]);
            }
        }

        foreach ($this->optMap as $name => $opt) {
            if (!isset($data[$name]) && $opt->getType() === OptDefinition::TYPE_BOOL) {
                $data[$name] = false;
            }
        }

        return $data;
    }

    /**
     * @param string $input
     * @return iterable<mixed>
     * @throws ParsingException for invalid args/opts
     */
    private function getArgsAndOpts(string $input): iterable
    {
        /** @var Token|null $incomplete */
        $incomplete = null;
        $argIndex = 0;
        foreach ($this->tokenize($input) as $token) {
            if ($incomplete) {
                if ($token->isOpt()) {
                    throw new ParsingException('Expected value for `%s`, but received new opt: `%s`', [
                        $incomplete->getKey(),
                        $token->getKey()
                    ]);
                } else {
                    $incomplete->resolveValue($token->getValue());
                    $token = $incomplete;
                    $incomplete = null;
                }
            }

            if ($token->isOpt()) {
                $optData = $this->createOpt($token);
                if ($optData === null) {
                    $incomplete = $token;
                } else {
                    yield $optData;
                }
            } else {
                yield $this->createArg($argIndex, $token);
                $argIndex++;
            }
        }

        if ($incomplete) {
            $incomplete->resolveValue('true');
            yield $this->createOpt($incomplete);
        }
    }

    /**
     * Tokenizes an input args string.
     *
     * Borrowed, with gratitude, from the Symfony Console component.
     *
     * @param string $input
     * @return iterable<mixed>
     * @see https://github.com/symfony/console/blob/5.x/Input/StringInput.php
     */
    private function tokenize(string $input): iterable
    {
        // Convert smart quotes to regular ones.
        $input = strtr($input, ['“' => '"', '”' => '"', "‘" => "'", "’" => "'"]);

        $length = strlen($input);
        $cursor = 0;
        while ($cursor < $length) {
            if (preg_match('/\s+/A', $input, $match, 0, $cursor)) {
                // Skip whitespace.
            } elseif (preg_match('/([^="\'\s]+?)(=?)(' . self::QUOTED_STRING . '+)/A', $input, $match, 0, $cursor)) {
                $value = str_replace(['"\'', '\'"', '\'\'', '""'], '', substr($match[3], 1, strlen($match[3]) - 2));
                yield new Token($match[1] . $match[2] . stripcslashes($value));
            } elseif (preg_match('/' . self::QUOTED_STRING . '/A', $input, $match, 0, $cursor)) {
                yield new Token(stripcslashes(substr($match[0], 1, strlen($match[0]) - 2)));
            } elseif (preg_match('/' . self::NORMAL_STRING . '/A', $input, $match, 0, $cursor)) {
                yield new Token(stripcslashes($match[1]));
            } else {
                // Should never happen (according to Symfony Console devs).
                throw new ParsingException('Unable to parse input near `... %s ...`', [substr($input, $cursor, 10)]);
            }

            $cursor += strlen($match[0]);
        }
    }

    /**
     * @param int $index
     * @param Token $token
     * @return mixed[]
     */
    private function createArg(int $index, Token $token): array
    {
        // Make sure the definition supports this arg.
        $argDef = $this->remainingArgs[$index] ?? null;
        unset($this->remainingArgs[$index]);
        if ($argDef === null) {
            throw new ParsingException('Too many args provided than defined');
        }

        // Validate and coerce the value to the correct type.
        $finalValue = $this->validateAndCoerceValueType($token->getValue(), $argDef->getType());
        if ($finalValue === null) {
            throw new ParsingException('Invalid value (`%s`) for arg `%s`; should be type: `%s`', [
                $token->getValue(),
                $argDef->getName(),
                $argDef->getType(),
            ]);
        }

        // Return the name-value tuple. Parser ultimately converts this to key-value pairs.
        return [$argDef->getName(), $finalValue];
    }

    /**
     * @param Token $token
     * @return mixed[]|null
     * @throws ParsingException
     */
    private function createOpt(Token $token): ?array
    {
        // Make sure the definition supports this opt.
        $optDef = $this->optMap[$token->getKey()] ?? null;
        if ($optDef === null) {
            throw new ParsingException('Invalid opt provided: `%s`', [$token->getKey()]);
        }

        // Make sure we have a value for the opt.
        if ($token->getValue() === null) {
            if ($optDef->getType() === OptDefinition::TYPE_BOOL) {
                // No value needed for bool flag. Set to true.
                $token->resolveValue('true');
            } else {
                // Value needed, but not yet known. Return null and let parsing continue.
                return null;
            }
        }

        // Validate and coerce the value to the correct type.
        $type = rtrim($optDef->getType(), '[]');
        $finalValue = $this->validateAndCoerceValueType($token->getValue(), $type);
        if ($finalValue === null) {
            throw new ParsingException('Invalid value (`%s`) for opt `%s`; should be type: `%s`', [
                $token->getValue(),
                $token->getKey(),
                $type,
            ]);
        }

        // Wrap array types in array to be merged with any previous/future values.
        $finalValue = $optDef->isArray() ? [$finalValue] : $finalValue;

        // Return the name-value tuple. Parser ultimately converts this to key-value pairs.
        return [$optDef->getName(), $finalValue];
    }

    /**
     * @param string $value
     * @param string $type
     * @return mixed
     */
    private function validateAndCoerceValueType(string $value, string $type)
    {
        switch ($type) {
            case ArgDefinition::TYPE_BOOL:
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                return ($value === null) ? null : (bool) $value;
            case ArgDefinition::TYPE_INT:
                $value = filter_var($value, FILTER_VALIDATE_INT);
                return ($value === false) ? null : (int) $value;
            case ArgDefinition::TYPE_FLOAT:
                $value = filter_var($value, FILTER_VALIDATE_FLOAT);
                return ($value === false) ? null : (float) $value;
            default:
                return (string) $value;
        }
    }
}
