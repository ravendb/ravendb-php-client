<?php

namespace RavenDB\Exceptions\Security;

use RavenDB\Exceptions\RavenException;
use Throwable;

class SecurityException extends RavenException
{
    public function __construct(string $message = "", ?Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }
}
