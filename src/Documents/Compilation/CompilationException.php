<?php

namespace RavenDB\Documents\Compilation;

use RavenDB\Exceptions\RavenException;
use Throwable;

// !status: DONE
class CompilationException extends RavenException
{
    public function __construct(string $message = "", ?Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }
}
