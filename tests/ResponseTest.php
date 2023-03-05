<?php
/** @noinspection ForgottenDebugOutputInspection */

namespace Hyqo\Http\Test;

use Hyqo\Http\HttpCode;
use Hyqo\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function test_set_code(): void
    {
        $response = (new Response())->setCode(HttpCode::FORBIDDEN);
        $this->assertEquals(HttpCode::FORBIDDEN->header(), $response->headers->all()[0]);

        $response = Response::create();
        $this->assertEquals(HttpCode::OK->header(), $response->headers->all()[0]);

        $response = Response::create(HttpCode::NO_CONTENT);
        $this->assertEquals(HttpCode::NO_CONTENT->header(), $response->headers->all()[0]);
    }

    /** @runInSeparateProcess */
    public function test_send_redirect(): void
    {
        $this->setInIsolation(true);

        (new Response())
            ->setHeader('Location', '/foo')
            ->send();

        $this->assertEquals(
            [
                'Location: /foo'
            ],
            xdebug_get_headers()
        );
    }

    /** @runInSeparateProcess */
    public function test_send_text(): void
    {
        ob_start();
        (new Response())
            ->setContentType('text/plain')
            ->setContent('foo')
            ->send();
        $content = ob_get_clean();

        $this->assertEquals(['Content-type: text/plain;charset=UTF-8'], xdebug_get_headers());
        $this->assertEquals('foo', $content);
    }

    /** @runInSeparateProcess */
    public function test_send_html_with_encoding(): void
    {
        ob_start();
        (new Response())
            ->setContentType('text/html', 'utf-8')
            ->setContent('foo')
            ->send();
        $content = ob_get_clean();

        $this->assertEquals(['Content-Type: text/html; charset=utf-8'], xdebug_get_headers());
        $this->assertEquals('foo', $content);
    }

    /** @runInSeparateProcess */
    public function test_send_json(): void
    {
        ob_start();

        (new Response())
            ->setContentType('application/json')
            ->setContent(json_encode(['foo']))
            ->send();

        $content = ob_get_clean();

        $this->assertEquals(['Content-Type: application/json'], xdebug_get_headers());
        $this->assertEquals('["foo"]', $content);
    }

    /** @runInSeparateProcess */
    public function test_send_attachment(): void
    {
        ob_start();
        (new Response())
            ->setContentType('application/json')
            ->setContent(json_encode(['foo']))
            ->sendAsAttachment('foo.json', 'application/json');

        $content = ob_get_clean();

        $this->assertEquals([
            'Content-Type: application/json',
            'Content-Disposition: attachment; filename="foo.json"',
            'Content-Length: 7',
        ], xdebug_get_headers());
        $this->assertEquals('["foo"]', $content);
    }
}
