<?php

namespace RavenDB\Type;

use RavenDB\Http\ResultInterface;

class StringResult implements ResultInterface
{
    private ?string $result;

    public function __construct(?string $result)
    {
        $this->result = $result;
    }

    public function __toString(): string
    {
        return $this->result ?? '';
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): void
    {
        $this->result = $result;
    }
}
