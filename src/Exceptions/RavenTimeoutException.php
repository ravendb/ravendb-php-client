<?php

namespace RavenDB\Exceptions;

class RavenTimeoutException extends RavenException
{
    private bool $failImmediately = false;

    public function isFailImmediately(): bool
    {
        return $this->failImmediately;
    }

    public function setFailImmediately(bool $failImmediately): void
    {
        $this->failImmediately = $failImmediately;
    }

}
