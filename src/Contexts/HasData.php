<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

use SlackPhp\Framework\Exception;

trait HasData
{
    /** @var array<int|string, mixed>  */
    protected array $data = [];

    /**
     * This constructor can (and likely should be) be overridden by trait users.
     *
     * @param array<int|string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function setData(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $this->data[$key] = $value;
        }
    }

    /**
     * Get a value from the data.
     *
     * @param string $key Key or dot-separated path to value in data.
     * @param bool $required Whether to throw an exception if the value is not set.
     * @return mixed
     */
    public function get(string $key, bool $required = false)
    {
        $value = $this->getDeep(explode('.', $key), $this->data);
        if ($required && $value === null) {
            $class = static::class;
            throw new Exception("Missing required value from {$class}: \"{$key}\".");
        }

        return $value;
    }

    /**
     * @param string[] $keys
     * @param bool $required Whether to throw an exception if none of the values are set.
     * @return mixed
     */
    public function getOneOf(array $keys, bool $required = false)
    {
        foreach ($keys as $key) {
            $value = $this->get($key);
            if ($value !== null) {
                return $value;
            }
        }

        if ($required) {
            $class = static::class;
            $list = implode(', ', array_map(fn (string $key) => "\"{$key}\"", $keys));

            throw new Exception("Missing required value from {$class}: one of {$list}.");
        }

        return null;
    }

    /**
     * @param string[] $keys
     * @param bool $required Whether to throw an exception if any of the values are set.
     * @return array<string, mixed>
     */
    public function getAllOf(array $keys, bool $required = false): array
    {
        $values = [];
        $missing = [];
        foreach ($keys as $key) {
            $value = $this->get($key);
            if ($value === null) {
                $missing[] = $key;
            } else {
                $values[$key] = $value;
            }
        }

        if ($required && !empty($missing)) {
            $class = static::class;
            $list = implode(', ', array_map(fn (string $key) => "\"{$key}\"", $missing));

            throw new Exception("Missing required values from {$class}: all of {$list}.");
        }

        return $values;
    }

    /**
     * @param mixed[] $keys
     * @param mixed[] $data
     * @return mixed
     */
    private function getDeep(array $keys, array &$data)
    {
        // Try the first key segment.
        $key = array_shift($keys);
        $value = $data[$key] ?? null;
        if ($value === null) {
            return null;
        }

        // If no more key segments, then it's are done. Don't recurse.
        if (empty($keys)) {
            return $value;
        }

        // If there is nothing to recurse into, don't recurse.
        if (!is_array($value)) {
            return null;
        }

        // Recurse into the next layer of the data with the remaining key segments.
        return $this->getDeep($keys, $value);
    }

    /**
     * Get all data as an associative array.
     *
     * Scrubs any sensitive keys.
     *
     * @return mixed[]
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
