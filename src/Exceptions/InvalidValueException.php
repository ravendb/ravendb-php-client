<?php

namespace RavenDB\Exceptions;

class InvalidValueException extends RavenException
{
    const MESSAGE = '%s invalid value: %s';

    public function __construct($valueObjectString = "", $value = "")
    {
        parent::__construct(sprintf($this::MESSAGE, $valueObjectString, $value));
    }
}
