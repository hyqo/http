<?php

namespace Hyqo\Http\Test\Pool;

use Hyqo\Http\Pool\FilePool;
use PHPUnit\Framework\TestCase;

class FilePoolTest extends TestCase
{
    public function test_single_file(): void
    {
        $filePool = new FilePool([
            'foo' => [
                'name' => 'foo.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => 'tmp/foo',
                'error' => 0,
                'size' => 123,
            ]
        ]);

        $this->assertEquals(
            [
                [
                    'name' => 'foo.jpg',
                    'type' => 'image/jpeg',
                    'tmp_name' => 'tmp/foo',
                    'error' => 0,
                    'size' => 123,
                ],
            ],
            $filePool->get('foo')
        );
    }

    public function test_multiple_file(): void
    {
        $filePool = new FilePool([
            'foo' => [
                'name' => [
                    'foo.jpg',
                    'bar.png',
                ],
                'type' => [
                    'image/jpeg',
                    'image/png',
                ],
                'tmp_name' => [
                    'tmp/foo',
                    'tmp/bar',
                ],
                'error' => [
                    0,
                    1,
                ],
                'size' => [
                    123,
                    456
                ],
            ]
        ]);

        $this->assertEquals(
            [
                [
                    'name' => 'foo.jpg',
                    'type' => 'image/jpeg',
                    'tmp_name' => 'tmp/foo',
                    'error' => 0,
                    'size' => 123,
                ],
                [
                    'name' => 'bar.png',
                    'type' => 'image/png',
                    'tmp_name' => 'tmp/bar',
                    'error' => 1,
                    'size' => 456,
                ],
            ],
            $filePool->get('foo')
        );
    }
}
