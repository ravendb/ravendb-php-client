<?php

namespace RavenDB\Utils;

class AtomicInteger
{

    private int $value;

    public function __construct(int $value = 0)
    {
        $this->value = $value;
    }

    public function get(): int
    {
        return $this->value;
    }

    public function incrementAndGet(): int
    {
        return ++$this->value;
    }
}
