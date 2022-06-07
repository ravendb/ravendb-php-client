<?php

namespace RavenDB\Type;

use RavenDB\Http\ResultInterface;

class BoolResult implements ResultInterface
{
    private bool $result = false;

    public function __construct(bool $result = false)
    {
        $this->result = $result;
    }

    public function __toBool(): bool
    {
        return $this->result;
    }

    public function getResult(): bool
    {
        return $this->result;
    }

    public function setResult(bool $result): void
    {
        $this->result = $result;
    }
}
