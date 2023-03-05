<?php

namespace Hyqo\Http\Test;

use Hyqo\Http\Method;
use PHPUnit\Framework\TestCase;

class MethodTest extends TestCase
{
    /** @dataProvider provide_is_safe_data */
    public function test_is_safe(bool $expected, Method $method): void
    {
        $this->assertEquals($expected, $method->isSafe());
    }

    protected function provide_is_safe_data(): \Generator
    {
        yield [true, Method::HEAD];
        yield [true, Method::GET];
        yield [true, Method::OPTIONS];
        yield [true, Method::TRACE];
        yield [false, Method::POST];
    }

    /** @dataProvider provide_is_idempotent_data */
    public function test_is_idempotent(bool $expected, Method $method): void
    {
        $this->assertEquals($expected, $method->isIdempotent());
    }

    protected function provide_is_idempotent_data(): \Generator
    {
        yield [true, Method::HEAD];
        yield [true, Method::GET];
        yield [true, Method::PUT];
        yield [true, Method::DELETE];
        yield [true, Method::OPTIONS];
        yield [false, Method::POST];
    }

    /** @dataProvider provide_is_cacheable_data */
    public function test_is_cacheable(bool $expected, Method $method): void
    {
        $this->assertEquals($expected, $method->isCacheable());
    }

    protected function provide_is_cacheable_data(): \Generator
    {
        yield [true, Method::HEAD];
        yield [true, Method::GET];
        yield [false, Method::POST];
    }
}
