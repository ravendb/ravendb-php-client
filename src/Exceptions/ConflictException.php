<?php

namespace RavenDB\Exceptions;

class ConflictException extends RavenException
{
    public function __construct(string $message, ?\Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }
}
