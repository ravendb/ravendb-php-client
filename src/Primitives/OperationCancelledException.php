<?php

namespace RavenDB\Primitives;

use Throwable;

class OperationCancelledException extends \RuntimeException
{
    private ?Throwable $cause = null;

    public function __construct($message = "", ?Throwable $cause = null)
    {
        parent::__construct($message);
        $this->cause = $cause;
    }

    public function getCause(): Throwable
    {
        return $this->cause;
    }

    public static function forCause(Throwable $cause): OperationCancelledException
    {
        return new OperationCancelledException("", $cause);
    }
}
