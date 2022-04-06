<?php

namespace RavenDB\Http;

class ServerExceptions
{
    public static array $exceptions = [
        'System.InvalidOperationException' => \InvalidArgumentException::class
    ];
}
