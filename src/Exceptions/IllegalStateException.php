<?php

namespace RavenDB\Exceptions;

class IllegalStateException extends RavenException
{
    public function __construct($message = "")
    {
        parent::__construct($message);
    }
}
