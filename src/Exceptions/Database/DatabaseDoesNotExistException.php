<?php

namespace RavenDB\Exceptions\Database;

use Throwable;
use RavenDB\Exceptions\RavenException;

class DatabaseDoesNotExistException extends RavenException
{
    public function __construct(string $message, ?Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }
}
