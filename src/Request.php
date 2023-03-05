<?php

namespace Hyqo\Http;

use Hyqo\Http\Exception\InvalidHostException;
use Hyqo\Http\Pool\AttributePool;
use Hyqo\Http\Pool\FilePool;
use Hyqo\Http\Pool\InputPool;
use Hyqo\Http\Pool\ServerPool;

use Hyqo\Utils\Ip\Ip;

use function Hyqo\String\s;

class Request
{
    readonly public RequestHeaders $headers;

    protected InputPool $query;

    protected InputPool $request;

    public InputPool $cookies;

    public FilePool $files;

    public ServerPool $server;

    public AttributePool $attributes;

    protected Method $method;

    protected ?string $content;

    protected ?string $host;

    protected int $port;

    protected ?string $baseUrl = null;

    protected ?string $pathInfo = null;

    protected ?string $requestUri = null;

    protected static array $trustedProxies = [];
    protected static int $trustedSet = 0;

    /**
     * @param array $query The GET parameters
     * @param array $request The POST parameters
     * @param array $cookies The COOKIE parameters
     * @param array $files The FILES parameters
     * @param array $server The SERVER parameters
     */
    public function __construct(
        array $query = [],
        array $request = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        protected string $input = 'php://input'
    ) {
        $this->query = new InputPool($query);
        $this->request = new InputPool($request);
        $this->cookies = new InputPool($cookies);
        $this->files = new FilePool($files);
        $this->server = new ServerPool($server);
        $this->headers = RequestHeaders::createFrom($this->server->all());

        $this->attributes = new AttributePool();

        if ($this->headers->contentType->isJson()
            && $this->isMethod(Method::POST)) {
            $data = json_decode($this->getContent(), true) ?? [];
            $this->request->replace($data);
        }

        if ($this->headers->contentType->isForm()
            && $this->isMethod(Method::PUT, Method::DELETE, Method::PATCH)) {
            parse_str($this->getContent(), $data);
            $this->request->replace($data);
        }
    }

    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }

    public function isMethod(Method ...$methods): bool
    {
        return in_array($this->getMethod(), $methods);
    }

    public function getMethod(): Method
    {
        return $this->method ??= Method::from(strtoupper($this->server->get('REQUEST_METHOD', Method::GET->value)));
    }

    public function getContent(): string
    {
        return $this->content ??= file_get_contents($this->input);
    }

    public function isSecure(): bool
    {
        if ($proto = $this->getTrustedValue(TrustedValue::PROTO)) {
            return $proto === 'https';
        }

        $https = $this->server->get('HTTPS', '');

        return !empty($https);
    }

    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    public function getHost(): string
    {
        return $this->host ??= (function (): string {
            if (!$host = $this->getTrustedValue(TrustedValue::HOST)) {
                $host = $this->headers->host ?? '';
            }

            $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

            if ($host && '' !== preg_replace('/[a-z\d\-]+\.?|^\[[:\d]+]$/', '', $host)) {
                throw new InvalidHostException(sprintf('Invalid Host "%s".', $host));
            }

            return $host;
        })();
    }

    public function getPort(): int
    {
        return $this->port ??= (function (): int {
            if ($port = $this->getTrustedValue(TrustedValue::PORT)) {
                return (int)$port;
            }

            if (
                ($host = $this->getTrustedValue(TrustedValue::HOST))
                ||
                ($host = $this->headers->get('Host'))
            ) {
                if ($port = Ip::port($host)) {
                    return $port;
                }

                return $this->isSecure() ? 443 : 80;
            }

            return (int)$this->server->get('SERVER_PORT');
        })();
    }

    public function getHttpHost(): string
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();

        if (('http' === $scheme && 80 === $port) || ('https' === $scheme && 443 === $port)) {
            return $this->getHost();
        }

        return $this->getHost() . ':' . $port;
    }

    public function getSchemeAndHttpHost(): string
    {
        return $this->getScheme() . '://' . $this->getHttpHost();
    }

    public function getUrl(): string
    {
        if (null !== $queryString = $this->getQueryString()) {
            $queryString = '?' . $queryString;
        }

        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo() . $queryString;
    }

    public function getQueryString(): ?string
    {
        if (null === $string = $this->server->get('QUERY_STRING')) {
            return null;
        }

        parse_str($string, $data);

        return http_build_query($data, '', '&', \PHP_QUERY_RFC3986);
    }

    public function generateUrlForPath(string $path): string
    {
        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $path;
    }

    public function getPathInfo(): string
    {
        return $this->pathInfo ??= (function (): string {
            if ('/' === ($requestUri = $this->getRequestUri())) {
                return $requestUri;
            }

            $requestUri = s($requestUri)->rightCrop('?');

            if (!$pathInfo = substr($requestUri, \strlen($this->getBaseUrlReal()))) {
                return '/';
            }

            return $pathInfo;
        })();
    }

    public function getBaseUrl(): string
    {
        if ($trustedPrefix = $this->getTrustedValue(TrustedValue::PREFIX)) {
            $trustedPrefix = rtrim($trustedPrefix, '/');
        } else {
            $trustedPrefix = '';
        }

        return $trustedPrefix . $this->getBaseUrlReal();
    }

    protected function getBaseUrlReal(): string
    {
        return $this->baseUrl ??= (function (): string {
            $requestUri = $this->getRequestUri();
            $prefix = dirname($this->server->get('SCRIPT_NAME', ''));

            if (
                preg_match(sprintf('/^%s(?:$|\/)/', preg_quote($prefix, '/')), rawurldecode($requestUri))
                &&
                preg_match(sprintf('/^(%%[[:xdigit:]]{2}|.){%d}/', \strlen($prefix)), $requestUri, $match)
            ) {
                return $match[0];
            }

            return '';
        })();
    }

    public function getRequestUri(): string
    {
        return $this->requestUri ??= $this->server->get('REQUEST_URI', '/');
    }

    public static function setTrustedProxy(array $proxies, int $setBitmask): void
    {
        self::$trustedProxies = $proxies;
        self::$trustedSet = $setBitmask;
    }

    public function isFromTrustedProxy(): bool
    {
        if (!self::$trustedProxies) {
            return false;
        }

        return IP::isMatch($this->server->get('REMOTE_ADDR', ''), self::$trustedProxies);
    }

    public function getTrustedValue(int $bit): array|int|string|null
    {
        if (!$this->isFromTrustedProxy()) {
            return null;
        }

        if (!(self::$trustedSet & $bit)) {
            return null;
        }

        return match ($bit) {
            TrustedValue::FOR => $this->headers->forwarded->for,
            TrustedValue::PROTO => $this->headers->forwarded->proto,
            TrustedValue::HOST => $this->headers->forwarded->host,
            TrustedValue::PORT => $this->headers->forwarded->port,
            TrustedValue::PREFIX => $this->headers->forwarded->prefix,
            default => null,
        };
    }

    public function getClientIp(): ?string
    {
        return $this->getClientIps()[0];
    }

    public function getClientIps(): array
    {
        if ($ips = $this->getTrustedValue(TrustedValue::FOR)) {
            return $ips;
        }

        $ip = $this->server->get('REMOTE_ADDR');

        return [$ip];
    }

    public function getContentType(): ?string
    {
        return $this->headers->contentType->mediaType;
    }

    public function get(string $name, $default = null)
    {
        if ($this->attributes->has($name)) {
            return $this->attributes->get($name);
        }

        if ($this->query->has($name)) {
            return $this->query->get($name);
        }

        if ($this->request->has($name)) {
            return $this->request->get($name);
        }

        return $default;
    }
}
