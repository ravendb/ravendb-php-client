<?php

namespace RavenDB\Exceptions\Compilation;

use RavenDB\Exceptions\RavenException;
use Throwable;

class CompilationException extends RavenException
{
    public function __construct(string $message = "", ?Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }
}
