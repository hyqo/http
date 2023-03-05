<?php

namespace Hyqo\Http\Pool;

abstract class Pool implements \IteratorAggregate, \Countable
{
    public function __construct(protected array $storage = [])
    {
    }

    public function all(): array
    {
        return $this->storage;
    }

    public function keys(): array
    {
        return array_keys($this->storage);
    }

    public function replace(array $parameters = []): void
    {
        $this->storage = $parameters;
    }

    public function add(array $parameters = []): void
    {
        $this->storage = array_replace($this->storage, $parameters);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->has($key) ? $this->storage[$key] : $default;
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->storage);
    }

    public function set(string $key, mixed $value): void
    {
        $this->storage[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->storage[$key]);
    }

    /**
     * @return \ArrayIterator<string, mixed>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->storage);
    }

    public function count(): int
    {
        return \count($this->storage);
    }
}
