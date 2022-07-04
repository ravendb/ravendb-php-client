<?php

namespace RavenDB\Exceptions;

use Throwable;

class CompareExchangeKeyTooBigException extends RavenException
{
    public function __construct(?string $message = null, Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }
}
