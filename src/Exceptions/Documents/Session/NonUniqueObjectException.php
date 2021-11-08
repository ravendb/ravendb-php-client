<?php

namespace RavenDB\Exceptions\Documents\Session;

use Exception;

class NonUniqueObjectException extends Exception
{
    public function __construct($message = "")
    {
        parent::__construct($message);
    }
}
