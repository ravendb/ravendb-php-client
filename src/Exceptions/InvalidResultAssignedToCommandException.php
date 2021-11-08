<?php

namespace RavenDB\Exceptions;

use Exception;

class InvalidResultAssignedToCommandException extends Exception
{
    const MESSAGE = 'Invalid result assigned to command. Command can accept only %s class.';

    public function __construct(string $expectedClass)
    {
        parent::__construct(sprintf($this::MESSAGE, $expectedClass));
    }
}
