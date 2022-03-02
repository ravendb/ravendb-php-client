<?php

namespace RavenDB\Exceptions\Security;

use Throwable;

class AuthorizationException extends SecurityException
{
    public function __construct(string $message = "", ?Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }
}
