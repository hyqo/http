<?php

namespace Hyqo\Http\Test;

use Hyqo\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    /** @runInSeparateProcess */
    public function test_create_from_globals(): void
    {
        $_GET = ['foo' => 'bar'];
        $_POST = ['bar' => 'foo'];

        $request = Request::createFromGlobals();

        $this->assertEquals('bar', $request->get('foo'));
        $this->assertEquals('foo', $request->get('bar'));
    }

    /**
     * @dataProvider provide_get_path_info_data
     */
    public function test_get_path_info($server, $expected): void
    {
        $request = new Request(server: $server);

        $this->assertSame($expected, $request->getPathInfo());
    }

    public function provide_get_path_info_data(): array
    {
        return [
            [
                [
                    'REQUEST_URI' => '/path/info',
                ],
                '/path/info'
            ],
            [
                [
                    'REQUEST_URI' => '/path%20test/info',
                ],
                '/path%20test/info'
            ],
            [
                [
                    'REQUEST_URI' => '?a=b',
                ],
                '/'
            ],
            [
                [],
                '/'
            ]
        ];
    }

    /**
     * @dataProvider provide_get_base_url_data
     */
    public function test_get_base_url($server, $expectedBaseUrl, $expectedPathInfo): void
    {
        $request = new Request(server: $server);

        $this->assertSame($expectedBaseUrl, $request->getBaseUrl(), 'baseUrl: ' . $server['REQUEST_URI']);
        $this->assertSame($expectedPathInfo, $request->getPathInfo(), 'pathInfo');
    }

    public function provide_get_base_url_data(): array
    {
        return [
            [
                [
                    'REQUEST_URI' => '/foo/bar/1234index.php/test',
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo/index.php',
                    'SCRIPT_NAME' => '/foo/index.php',
                ],
                '/foo',
                '/bar/1234index.php/test',
            ],
            [
                [
                    'REQUEST_URI' => '/foo/bar/1234index.php/test',
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/index.php',
                    'SCRIPT_NAME' => '/index.php',
                ],
                '',
                '/foo/bar/1234index.php/test',
            ],
            [
                [
                    'REQUEST_URI' => '/foo%20bar/',
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo bar/app.php',
                    'SCRIPT_NAME' => '/foo bar/app.php',
                ],
                '/foo%20bar',
                '/',
            ],
            [
                [
                    'REQUEST_URI' => '/foo%20bar/home',
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo bar/app.php',
                    'SCRIPT_NAME' => '/foo bar/app.php',
                ],
                '/foo%20bar',
                '/home',
            ],
            [
                [
                    'REQUEST_URI' => '/foo%20bar/app.php/home',
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo bar/app.php',
                    'SCRIPT_NAME' => '/foo bar/app.php',
                ],
                '/foo%20bar',
                '/app.php/home',
            ],
            [
                [
                    'REQUEST_URI' => '/foo%20bar/app.php/home%3Dbaz',
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo bar/app.php',
                    'SCRIPT_NAME' => '/foo bar/app.php',
                ],
                '/foo%20bar',
                '/app.php/home%3Dbaz',
            ],
            [
                [
                    'REQUEST_URI' => '/foo/bar+baz',
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo/app.php',
                    'SCRIPT_NAME' => '/foo/app.php',
                ],
                '/foo',
                '/bar+baz',
            ],
            [
                [
                    'REQUEST_URI' => '/sub/foo/bar',
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo/app.php',
                    'SCRIPT_NAME' => '/foo/app.php',
                ],
                '',
                '/sub/foo/bar',
            ],
            [
                [
                    'REQUEST_URI' => '/sub/foo/bar/baz',
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo/app2.php',
                    'SCRIPT_NAME' => '/foo/app2.php',
                ],
                '',
                '/sub/foo/bar/baz',
            ],
            [
                [
                    'REQUEST_URI' => '/foo/api/bar',
                    'SCRIPT_FILENAME' => '/var/www/api/index.php',
                    'SCRIPT_NAME' => '/api/index.php',
                ],
                '',
                '/foo/api/bar',
            ],
            [
                [
                    'REQUEST_URI' => '/webmaster',
                    'SCRIPT_FILENAME' => '/foo/bar/web/index.php',
                    'SCRIPT_NAME' => '/web/index.php',
                ],
                '',
                '/webmaster',
            ]
        ];
    }

    /**
     * @dataProvider provide_get_request_uri_data
     */
    public function test_get_request_uri(string $expected, string $requestUri): void
    {
        $request = new Request(server: ['REQUEST_URI' => $requestUri]);

        $this->assertSame($expected, $request->getRequestUri());
    }

    public function provide_get_request_uri_data(): \Generator
    {
        yield ['/foo', '/foo'];
        yield ['//bar/foo', '//bar/foo'];
        yield ['///bar/foo', '///bar/foo'];
        yield ['/foo?bar=baz', '/foo?bar=baz'];
        yield ['/foo?bar=baz', '/foo?bar=baz'];
    }

    /** @dataProvider provide_is_secure_data */
    public function test_is_secure(bool $expected, array $server): void
    {
        $request = new Request(server: $server);

        $this->assertEquals($expected, $request->isSecure());
    }

    protected function provide_is_secure_data(): \Generator
    {
        yield [false, []];

        yield [false, ['HTTPS' => '']];

        yield [true, ['HTTPS' => 'on']];
    }

    public function test_get_host(): void
    {
        $request = new Request(server: ['HTTP_HOST' => 'google.com']);
        $this->assertEquals('google.com', $request->getHost());

        $request = new Request(server: ['HTTP_HOST' => 'google.com:80']);
        $this->assertEquals('google.com', $request->getHost());
    }

    /** @dataProvider provide_get_http_host_data */
    public function test_get_http_host(string $expected, array $server): void
    {
        $request = new Request(server: $server);

        $this->assertEquals($expected, $request->getHttpHost());
    }

    protected function provide_get_http_host_data(): \Generator
    {
        yield [
            'foo.com',
            ['HTTP_HOST' => 'foo.com']
        ];

        yield [
            'foo.com',
            ['HTTP_HOST' => 'foo.com', 'HTTPS' => 'on']
        ];

        yield [
            'foo.com:8080',
            ['HTTP_HOST' => 'foo.com:8080']
        ];

        yield [
            'foo.com:8443',
            ['HTTP_HOST' => 'foo.com:8443', 'HTTPS' => 'on']
        ];
    }

    public function test_very_long_host(): void
    {
        foreach (
            [
                str_repeat('foo.', 90000) . 'bar',
                '[' . str_repeat(':', 90000) . ']'
            ] as $host
        ) {
            $start = microtime(true);

            $request = new Request(server: ['HTTP_HOST' => $host]);
            $this->assertEquals($host, $request->getHost());
            $this->assertLessThan(.1, microtime(true) - $start);
        }
    }

    public function test_get_scheme(): void
    {
        $request = new Request(server: ['HTTPS' => 'on']);
        $this->assertEquals('https', $request->getScheme());

        $request = new Request(server: ['HTTPS' => '']);
        $this->assertEquals('http', $request->getScheme());
    }

    /** @dataProvider provide_get_scheme_and_http_host_data */
    public function test_get_scheme_and_http_host(string $expected, array $server): void
    {
        $request = new Request(server: $server);

        $this->assertEquals($expected, $request->getSchemeAndHttpHost());
    }

    protected function provide_get_scheme_and_http_host_data(): \Generator
    {
        yield [
            'http://foo.com',
            ['HTTP_HOST' => 'foo.com']
        ];

        yield [
            'https://foo.com',
            ['HTTP_HOST' => 'foo.com', 'HTTPS' => 'on']
        ];

        yield [
            'http://foo.com:8080',
            ['HTTP_HOST' => 'foo.com:8080']
        ];

        yield [
            'https://foo.com:8443',
            ['HTTP_HOST' => 'foo.com:8443', 'HTTPS' => 'on']
        ];
    }

    public function test_get_port(): void
    {
        $request = new Request(server: ['SERVER_PORT' => 8080]);
        $this->assertEquals(8080, $request->getPort());

        $request = new Request(server: ['HTTP_HOST' => 'localhost', 'HTTPS' => 'on']);
        $this->assertEquals(443, $request->getPort());

        $request = new Request(server: ['HTTP_HOST' => 'localhost']);
        $this->assertEquals(80, $request->getPort());
    }

    public function test_get_client_ip(): void
    {
        $request = new Request(server: ['REMOTE_ADDR' => '127.0.0.1']);
        $this->assertEquals('127.0.0.1', $request->getClientIp());

        $request = new Request(server: ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_FOR' => '::1']);
        $this->assertEquals('127.0.0.1', $request->getClientIp());
    }

    public function test_get_query_string(): void
    {
        $request = new Request(server: ['QUERY_STRING' => 'foo=bar&bar=foo bar']);

        $this->assertEquals('foo=bar&bar=foo%20bar', $request->getQueryString());
    }

    public function test_generate_url_for_path(): void
    {
        $request = new Request(server: ['HTTP_HOST' => 'foo.com']);

        $this->assertEquals('http://foo.com/bar', $request->generateUrlForPath('/bar'));
    }

    /** @dataProvider provide_get_url_data */
    public function test_get_url(string $expected, array $server): void
    {
        $request = new Request(server: $server);

        $this->assertEquals($expected, $request->getUrl());
    }

    protected function provide_get_url_data(): \Generator
    {
        yield [
            'http://foo.com/',
            [
                'HTTP_HOST' => 'foo.com',
            ]
        ];

        yield [
            'https://foo.com/test?foo=bar',
            [
                'HTTP_HOST' => 'foo.com',
                'HTTPS' => 'on',
                'REQUEST_URI' => '/test?foo=bar',
                'QUERY_STRING' => 'foo=bar',
            ]
        ];

        yield [
            'https://foo.com/foo/test?foo=bar',
            [
                'HTTP_HOST' => 'foo.com',
                'HTTPS' => 'on',
                'REQUEST_URI' => '/foo/test?foo=bar',
                'QUERY_STRING' => 'foo=bar',
                'SCRIPT_FILENAME' => 'C:/app/public_html/foo/index.php',
                'SCRIPT_NAME' => '/foo/index.php',
            ]
        ];
    }

    public function test_get_parameter(): void
    {
        $request = new Request(query: ['foo' => 'bar']);
        $request->attributes->set('foo', 'attr');
        $this->assertEquals('attr', $request->get('foo'));

        $request->attributes->remove('foo');
        $this->assertEquals('bar', $request->get('foo'));

        $this->assertNull($request->get('bar'));
    }

    public function test_json_request(): void
    {
        $input = tempnam('/tmp', 'input');
        file_put_contents($input, json_encode(['foo' => 'bar']));
        $request = new Request(
            server: ['HTTP_CONTENT_TYPE' => 'application/json', 'REQUEST_METHOD' => 'POST'],
            input: $input
        );
        unlink($input);

        $this->assertEquals('bar', $request->get('foo'));
        $this->assertNull($request->get('bar'));
    }

    public function test_form_request(): void
    {
        $input = tempnam('/tmp', 'input');
        file_put_contents($input, http_build_query(['foo' => 'bar']));
        $request = new Request(
            server: ['HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded', 'REQUEST_METHOD' => 'PUT'],
            input: $input
        );
        unlink($input);

        $this->assertEquals('bar', $request->get('foo'));
        $this->assertNull($request->get('bar'));
    }

    public function test_get_content_type(): void
    {
        $request = new Request(server: ['HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $this->assertEquals('application/x-www-form-urlencoded', $request->getContentType());
    }
}
