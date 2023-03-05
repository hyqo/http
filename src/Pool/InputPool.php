<?php

namespace Hyqo\Http\Pool;

use Hyqo\Http\Exception\InvalidFilterCallableException;

class InputPool extends Pool
{
    public function get(string $key, mixed $default = ''): string|array|null
    {
        $value = parent::get($key, $default);

        return is_string($value) ? trim($value) : $value;
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int)$this->filter($key, $default, \FILTER_SANITIZE_NUMBER_INT);
    }

    public function getFloat(string $key, float $default = 0): float
    {
        return (float)$this->filter($key, $default, \FILTER_SANITIZE_NUMBER_FLOAT, \FILTER_FLAG_ALLOW_FRACTION);
    }

    public function getBoolean(string $key, bool $default = false): bool
    {
        return $this->filter($key, $default, \FILTER_VALIDATE_BOOLEAN);
    }

    public function filter(
        string $key,
        $default = null,
        int $filter = \FILTER_DEFAULT,
        int|array|callable $options = []
    ) {
        $value = $this->get($key, $default);

        $filterOptions = [
            'flags' => \FILTER_FLAG_NONE,
            ...(is_array($options) ? $options : [])
        ];

        if (is_numeric($options)) {
            $filterOptions['flags'] = $options;
        }

        if (is_array($value) && !isset($options['flags'])) {
            $filterOptions['flags'] |= \FILTER_REQUIRE_ARRAY;
        }

        if (is_callable($options)) {
            $filterOptions['options'] = $options;
        }

        if (\FILTER_CALLBACK & $filter) {
            $callable = ($filterOptions['options'] ?? null);

            if (is_string($callable) && !function_exists($callable)) {
                throw new InvalidFilterCallableException(
                    sprintf(
                        'The function named "%s" passed to "%s()" does not exists',
                        $callable,
                        __METHOD__
                    )
                );
            }

            if (!is_string($callable) && !($callable instanceof \Closure)) {
                throw new InvalidFilterCallableException(
                    sprintf(
                        'A Closure must be passed to "%s()" when FILTER_CALLBACK is used, "%s" given.',
                        __METHOD__,
                        gettype($callable)
                    )
                );
            }
        }

        return filter_var($value, $filter, $filterOptions);
    }
}
