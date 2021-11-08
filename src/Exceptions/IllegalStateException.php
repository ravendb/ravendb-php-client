<?php

namespace RavenDB\Exceptions;

use Exception;

class IllegalStateException extends Exception
{
    public function __construct($message = "")
    {
        parent::__construct($message);
    }
}
