<?php

namespace RavenDB\Exceptions\Database;

use RavenDB\Exceptions\RavenException;

class DatabaseDoesNotExistException extends RavenException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

//    public DatabaseDoesNotExistException(String message, Throwable cause) {
//        super(message, cause);
//    }
}
