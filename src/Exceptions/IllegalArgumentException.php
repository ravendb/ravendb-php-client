<?php

namespace RavenDB\Exceptions;

class IllegalArgumentException extends RavenException
{
    public function __construct($message = "")
    {
        parent::__construct($message);
    }
}
