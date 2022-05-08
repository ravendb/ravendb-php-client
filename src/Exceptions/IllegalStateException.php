<?php

namespace RavenDB\Exceptions;

use Throwable;

class IllegalStateException extends RavenException
{
    public function __construct($message = "", ?Throwable $exception = null)
    {
        parent::__construct($message, $exception);
    }
}
