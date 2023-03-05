<?php

namespace Hyqo\Http\Test\Pool;

use Hyqo\Http\Pool\Pool;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    private function createPool($parameters = []): Pool
    {
        return new class($parameters) extends Pool {

        };
    }

    private function parameters(): array
    {
        return [
            'int' => 999,
            'string' => 'foo',
            'array' => ['foo' => 'bar'],
            'object' => (object)['foo' => 'bar'],
        ];
    }

    public function test_has(): void
    {
        $pool = $this->createPool($this->parameters());

        $this->assertFalse($pool->has('foo'));
        $this->assertTrue($pool->has('string'));
    }

    public function test_get(): void
    {
        $pool = $this->createPool($this->parameters());

        $this->assertEquals(999, $pool->get('int'));
        $this->assertEquals('foo', $pool->get('string'));
//        $this->assertEquals(['foo' => 'bar'], $pool->get('array'));
//        $this->assertEquals((object)['foo' => 'bar'], $pool->get('object'));
    }

    public function test_get_default(): void
    {
        $pool = $this->createPool();

        $this->assertEquals('bar', $pool->get('foo', 'bar'));
    }

    public function test_set(): void
    {
        $pool = $this->createPool($this->parameters());
        $pool->set('string', 'bar');

        $this->assertEquals('bar', $pool->get('string'));
    }

    public function test_remove(): void
    {
        $pool = $this->createPool($this->parameters());
        $pool->remove('string');

        $this->assertNull($pool->get('string'));
        $this->assertFalse($pool->has('string'));
    }

    public function test_all(): void
    {
        $pool = $this->createPool($this->parameters());

        $this->assertEquals($this->parameters(), $pool->all());
    }

    public function test_keys(): void
    {
        $pool = $this->createPool($this->parameters());

        $this->assertEquals(array_keys($this->parameters()), $pool->keys());
    }

    public function test_replace(): void
    {
        $pool = $this->createPool($this->parameters());

        $pool->replace(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $pool->all());
    }

    public function test_add(): void
    {
        $pool = $this->createPool($this->parameters());

        $pool->add([
            'string' => 'bar',
            'foo' => 'bar',
        ]);

        $this->assertEquals('bar', $pool->get('foo'));
        $this->assertEquals('bar', $pool->get('string'));
    }

    public function test_count(): void
    {
        $pool = $this->createPool($this->parameters());

        $this->assertEquals(count($this->parameters()), $pool->count());
    }

    public function test_iterator(): void
    {
        $pool = $this->createPool($this->parameters());

        $this->assertInstanceOf(\ArrayIterator::class, $pool->getIterator());
    }
}
