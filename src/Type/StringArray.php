<?php

namespace RavenDB\Type;

class StringArray extends \ArrayObject implements \JsonSerializable
{
    public function offsetSet($key, $value)
    {
        if (!is_string($value)) {
            throw new \TypeError(
                sprintf("Only values of type string are supported")
            );
        }

        parent::offsetSet($key, $value);
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }
}
