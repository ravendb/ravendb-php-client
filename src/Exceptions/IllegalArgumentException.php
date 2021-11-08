<?php

namespace RavenDB\Exceptions;

use Exception;
use Throwable;

class IllegalArgumentException extends Exception implements Throwable
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
