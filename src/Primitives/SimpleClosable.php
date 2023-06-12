<?php

namespace RavenDB\Primitives;

use Closure;

class SimpleClosable implements CleanCloseable
{
    public ?Closure $closeMethod = null;

    public function getCloseMethod(): ?Closure
    {
        return $this->closeMethod;
    }

    public function setCloseMethod(?Closure $closeMethod): void
    {
        $this->closeMethod = $closeMethod;
    }

    public function close(): void
    {
        $closeMethod = $this->closeMethod;

        $closeMethod();
    }
}
