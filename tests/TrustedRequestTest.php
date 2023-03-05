<?php
/** @noinspection ForgottenDebugOutputInspection */

namespace Hyqo\Http\Test;

use Hyqo\Http\Exception\InvalidHostException;
use Hyqo\Http\Request;
use Hyqo\Http\TrustedValue;
use PHPUnit\Framework\TestCase;

/** @runTestsInSeparateProcesses */
class TrustedRequestTest extends TestCase
{
    protected function server(array $server = []): array
    {
        return [...$server, 'REMOTE_ADDR' => '127.0.0.1'];
    }

    public function test_is_from_trusted_proxy(): void
    {
        $request = new Request(server: $this->server());

        $this->assertFalse($request->isFromTrustedProxy());

        Request::setTrustedProxy(['127.0.0.1'], 0);

        $this->assertTrue($request->isFromTrustedProxy());
    }

    public function test_is_secure(): void
    {
        Request::setTrustedProxy(['127.0.0.1'], TrustedValue::PROTO);
        $request = new Request(server: $this->server(['HTTP_X_FORWARDED_PROTO' => 'https']));

        $this->assertTrue($request->isSecure());
    }

    public function test_get_host(): void
    {
        Request::setTrustedProxy(['127.0.0.1'], TrustedValue::HOST);

        $request = new Request(
            server: $this->server([
                'HTTP_HOST' => 'google.com',
                'HTTP_X_FORWARDED_HOST' => 'cdn.com',
            ])
        );
        $this->assertEquals('cdn.com', $request->getHost());

        $request = new Request(
            server: $this->server([
                'HTTP_HOST' => 'google.com',
                'HTTP_X_FORWARDED_HOST' => 'evil_.com',
            ])
        );

        try {
            $request->getHost();
            $this->fail('Should throw an exception');
        } catch (InvalidHostException $exception) {
            $this->assertEquals('Invalid Host "evil_.com".', $exception->getMessage());
        }
    }

    public function test_get_port(): void
    {
        Request::setTrustedProxy(['127.0.0.1'], TrustedValue::HOST);

        $request = new Request(server: $this->server(['HTTP_X_FORWARDED_HOST' => 'localhost:1234']));
        $this->assertEquals(1234, $request->getPort());

        Request::setTrustedProxy(['127.0.0.1'], TrustedValue::PORT);
        $request = new Request(server: $this->server(['HTTP_X_FORWARDED_PORT' => '123',]));
        $this->assertEquals(123, $request->getPort());
    }

    public function test_get_base_url(): void
    {
        Request::setTrustedProxy(['127.0.0.1'], TrustedValue::PREFIX);

        $request = new Request(server: $this->server(['HTTP_X_FORWARDED_PREFIX' => '/foo/']));

        $this->assertSame('/foo', $request->getBaseUrl());
    }

    public function test_generate_url_for_path(): void
    {
        Request::setTrustedProxy(['127.0.0.1'],
            TrustedValue::PREFIX | TrustedValue::HOST | TrustedValue::PORT | TrustedValue::PROTO
        );

        $request = new Request(
            server: $this->server([
                'HTTP_HOST' => 'example.com:8080',
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_HOST' => 'foo.com',
                'HTTP_X_FORWARDED_PORT' => '8443',
                'HTTP_X_FORWARDED_PREFIX' => '/foo/',
            ])
        );

        $this->assertEquals('https://foo.com:8443/foo/bar', $request->generateUrlForPath('/bar'));
    }

    public function test_get_client_ip(): void
    {
        Request::setTrustedProxy(['127.0.0.1'], TrustedValue::FOR);

        $request = new Request(server: $this->server(['HTTP_X_FORWARDED_FOR' => '::1,::2']));
        $this->assertEquals('::1', $request->getClientIp());
        $this->assertEquals(['::1', '::2'], $request->getClientIps());
    }
}
