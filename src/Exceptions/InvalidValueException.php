<?php

namespace RavenDB\Exceptions;

use Exception;

class InvalidValueException extends Exception
{
    const MESSAGE = '%s invalid value: %s';

    public function __construct($valueObjectString = "", $value = "")
    {
        parent::__construct(sprintf($this::MESSAGE, $valueObjectString, $value));
    }
}
