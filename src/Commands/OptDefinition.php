<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Commands;

class OptDefinition
{
    public const TYPE_STRING = 'string';
    public const TYPE_INT = 'int';
    public const TYPE_FLOAT = 'float';
    public const TYPE_BOOL = 'bool';
    public const TYPE_STRING_ARRAY = 'string[]';
    public const TYPE_INT_ARRAY = 'int[]';
    public const TYPE_FLOAT_ARRAY = 'float[]';

    private string $name;
    private ?string $shortName;
    private string $description;
    private string $type;

    public function __construct(
        string $name,
        string $type = self::TYPE_BOOL,
        ?string $shortName = null,
        string $description = ''
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->shortName = $shortName;
        $this->description = $description;
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
    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isArray(): bool
    {
        return substr($this->type, -2) === '[]';
    }

    public function getFormat(): string
    {
        $format = "[--{$this->name}";
        if ($this->shortName !== null) {
            $format .= "|-{$this->shortName}";
        }

        if (in_array($this->type, [self::TYPE_STRING, self::TYPE_INT, self::TYPE_FLOAT])) {
            $format .= " <{$this->type}>]";
        } elseif (in_array($this->type, [self::TYPE_STRING_ARRAY, self::TYPE_INT_ARRAY, self::TYPE_FLOAT_ARRAY])) {
            $type = rtrim($this->type, '[]');
            $format .= " <{$type}>]...";
        } else {
            $format .= ']';
        }

        return $format;
    }
}
