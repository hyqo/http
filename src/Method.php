<?php

namespace Hyqo\Http;

enum Method: string
{
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';

    case GET = 'GET';

    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case PATCH = 'PATCH';

    public function isSafe(): bool
    {
        return match ($this) {
            self::HEAD, self::GET, self::OPTIONS, self::TRACE => true,
            default => false
        };
    }

    public function isIdempotent(): bool
    {
        return match ($this) {
            self::HEAD, self::GET, self::PUT, self::DELETE, self::OPTIONS => true,
            default => false
        };
    }

    public function isCacheable(): bool
    {
        return match ($this) {
            self::HEAD, self::GET => true,
            default => false
        };
    }
}
