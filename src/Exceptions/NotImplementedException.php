<?php

namespace RavenDB\Exceptions;

class NotImplementedException extends RavenException
{
    const MESSAGE = 'The feature is not implemented yet.';

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? self::MESSAGE);
    }
}
