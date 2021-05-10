<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Contexts;

use SlackPhp\Framework\Exception;

use const ARRAY_FILTER_USE_KEY;

trait HasData
{
    /** @var array<string, mixed>  */
    protected array $data = [];

    /** @var array<string, bool> */
    protected array $sensitive = [];

    /**
     * This constructor can (and likely should be) be overridden by trait users.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    private function setData(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            if ($value instanceof SensitiveValue) {
                $this->sensitive[$key] = true;
                $value = $value->getRawValue();
            }

            $this->data[$key] = $value;
        }
    }

    /**
     * Get a value from the data.
     *
     * @param string $key Key or dot-separated path to value in data.
     * @param bool $required Whether to throw an exception if the value is not set.
     * @return string|array|int|float|bool|null
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
     * @return string|array|int|float|bool|null
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
     * @return array<string, string|array|int|float|bool|null>
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
     * @param array $keys
     * @param array $data
     * @return string|array|int|float|bool|null
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
     * @return array
     */
    public function toArray(): array
    {
        return array_filter($this->data, fn (string $key) => !isset($this->sensitive[$key]), ARRAY_FILTER_USE_KEY);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
