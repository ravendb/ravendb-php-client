<?php

namespace RavenDB\Exceptions;

class ClientVersionMismatchException extends RavenException
{
    public function __construct(string $message = '', ?\Throwable $cause = null)
    {
        $msg = $message;
        if ($cause) {
            $msg .= ' | ' . $cause->getMessage();

        }
        parent::__construct($msg);
    }
}
