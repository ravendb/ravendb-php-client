<?php

namespace RavenDB\Exceptions;

// !status: DONE
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
