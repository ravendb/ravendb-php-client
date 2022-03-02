<?php

namespace RavenDB\Exceptions;

class UnsupportedEncodingException extends RavenException
{
    const MESSAGE = 'Unsupported encoding exception';

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? self::MESSAGE);
    }
}
