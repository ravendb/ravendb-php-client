<?php

namespace RavenDB\Exceptions;

use Exception;
use Throwable;

// !status: DONE
class RavenException extends Exception
{
    private bool $reachedLeader = false;

    private ?Throwable $cause = null;

    public function __construct(string $message = "", ?Throwable $cause = null)
    {
        parent::__construct($message);

        $this->cause = $cause;
    }

    public function getCause(): ?Throwable
    {
        return $this->cause;
    }

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
