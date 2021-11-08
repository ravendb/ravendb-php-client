<?php

namespace RavenDB\Exceptions;

use Exception;

class RavenException extends Exception
{
    private bool $reachedLeader = false;

    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    // if we need this cause then it should be added to constructor
//    public RavenException(String message, Throwable cause) {
//        super(message, cause);
//    }
//
    public function isReachedLeader(): bool
    {
        return $this->reachedLeader;
    }

    public function setReachedLeader(bool $reachedLeader): void
    {
        $this->reachedLeader = $reachedLeader;
    }

    public static function generic(string $error, string $json): RavenException
    {
        return new RavenException($error . '. Response: ' . $json);
    }
}
