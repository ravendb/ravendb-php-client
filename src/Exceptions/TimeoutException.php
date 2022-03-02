<?php

namespace RavenDB\Exceptions;

use Throwable;

// !status: DONE
class TimeoutException extends RavenException
{
    public function __construct(string $message, ?Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }
}
