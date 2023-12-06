<?php

namespace RavenDB\Exceptions;

use RuntimeException;
use Throwable;

class RavenException extends RuntimeException
{
    private bool $reachedLeader = false;

    private ?Throwable $cause = null;

    public function __construct(string $message = "", ?Throwable $cause = null)
    {
        parent::__construct($message, 0, $cause);

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
