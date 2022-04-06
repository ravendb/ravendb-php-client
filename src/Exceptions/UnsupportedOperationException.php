<?php

namespace RavenDB\Exceptions;

class UnsupportedOperationException extends RavenException
{
    const MESSAGE = 'Unsupported operation exception';

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? self::MESSAGE);
    }
}
