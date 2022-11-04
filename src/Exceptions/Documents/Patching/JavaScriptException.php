<?php

namespace RavenDB\Exceptions\Documents\Patching;

use RavenDB\Exceptions\RavenException;
use Throwable;

class JavaScriptException extends RavenException
{
    public function __construct(string $message = "", ?Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }
}
