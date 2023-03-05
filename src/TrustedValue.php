<?php

namespace Hyqo\Http;

class TrustedValue
{
    public const FOR = 1 << 0;
    public const PORT = 1 << 1;
    public const HOST = 1 << 2;
    public const PROTO = 1 << 3;
    public const PREFIX = 1 << 4;
}
