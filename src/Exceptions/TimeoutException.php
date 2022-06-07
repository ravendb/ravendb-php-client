<?php

namespace RavenDB\Exceptions;

use Throwable;

// !status: DONE
class TimeoutException extends RavenException
{
    const MESSAGE = 'Time expired';

    public function __construct(string $message = self::MESSAGE, ?Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }
}
