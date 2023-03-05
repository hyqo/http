<?php

namespace Hyqo\Http\Test\Pool;

use Hyqo\Http\Exception\InvalidFilterCallableException;
use Hyqo\Http\Pool\InputPool;
use PHPUnit\Framework\TestCase;

class InputPoolTest extends TestCase
{
    public function test_scalar(): void
    {
        $pool = new InputPool([
            'int' => '123',
            'float' => '.123',
            'bool_true' => '1',
            'bool_false' => 'no',
        ]);

        $this->assertEquals(123, $pool->getInt('int'));
        $this->assertEquals(0.123, $pool->getFloat('float'));
        $this->assertTrue($pool->getBoolean('bool_true'));
        $this->assertFalse($pool->getBoolean('bool_false'));
    }

    public function test_filter(): void
    {
        $pool = new InputPool([
            'valid_email' => 'foo@bar.test',
            'invalid_email' => 'foo',
            'emails' => [
                'foobar@example.com',
                'invalid'
            ]
        ]);

        $this->assertFalse($pool->filter('undefined_key', filter: FILTER_VALIDATE_EMAIL));
        $this->assertFalse($pool->filter('invalid_email', filter: FILTER_VALIDATE_EMAIL));
        $this->assertNotFalse($pool->filter('valid_email', filter: FILTER_VALIDATE_EMAIL));
        $this->assertEquals(['foobar@example.com', false], $pool->filter('emails', filter: FILTER_VALIDATE_EMAIL));
    }

    public function test_filter_callback(): void
    {
        $pool = new InputPool([
            'foo' => 'bar',
        ]);

        $this->assertEquals(
            'BAR',
            $pool->filter('foo', filter: FILTER_CALLBACK, options: ['options' => 'strtoupper'])
        );
        $this->assertEquals(
            'BAR',
            $pool->filter('foo', filter: FILTER_CALLBACK, options: fn($value) => strtoupper($value))
        );
    }

    public function test_filter_flags(): void
    {
        $pool = new InputPool([
            'foo' => 'bar',
        ]);

        $this->assertEquals(
            'bar',
            $pool->filter('foo', filter: FILTER_SANITIZE_ENCODED, options: FILTER_FLAG_STRIP_BACKTICK)
        );
    }

    public function test_invalid_filter_function(): void
    {
        $pool = new InputPool();

        $this->expectException(InvalidFilterCallableException::class);
        $this->expectExceptionMessage('The function named');
        $pool->filter('foo', filter: FILTER_CALLBACK, options: ['options' => 'foo']);
    }

    public function test_invalid_filter_closure(): void
    {
        $pool = new InputPool();

        $this->expectException(InvalidFilterCallableException::class);
        $this->expectExceptionMessage('A Closure must be passed');
        $pool->filter('foo', filter: FILTER_CALLBACK, options: ['options' => 1]);
    }
}
